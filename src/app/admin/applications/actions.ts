"use server";

import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { revalidatePath } from "next/cache";

export async function approveApplication(id: string) {
  const db = await getDb();

  const message = {
    _id: new ObjectId(),
    message_type: "approve" as const,
    message_text: "Your application has been approved. Welcome aboard!",
    created_at: new Date(),
  };

  await db.collection("partner_applications").updateOne(
    { _id: new ObjectId(id) },
    {
      $set: { status: "approved", updated_at: new Date() },
      $push: { messages: message as never },
    }
  );

  revalidatePath("/admin/applications");
  revalidatePath(`/admin/applications/${id}`);
}

export async function rejectApplication(id: string, reason: string) {
  const db = await getDb();

  const message = {
    _id: new ObjectId(),
    message_type: "reject" as const,
    message_text: reason || "Your application has been rejected.",
    created_at: new Date(),
  };

  await db.collection("partner_applications").updateOne(
    { _id: new ObjectId(id) },
    {
      $set: { status: "rejected", updated_at: new Date() },
      $push: { messages: message as never },
    }
  );

  revalidatePath("/admin/applications");
  revalidatePath(`/admin/applications/${id}`);
}

export async function requestInfo(id: string, message: string) {
  const db = await getDb();

  const msg = {
    _id: new ObjectId(),
    message_type: "request_info" as const,
    message_text: message || "We need additional information about your application.",
    created_at: new Date(),
  };

  await db.collection("partner_applications").updateOne(
    { _id: new ObjectId(id) },
    {
      $set: { status: "info_requested", updated_at: new Date() },
      $push: { messages: msg as never },
    }
  );

  revalidatePath("/admin/applications");
  revalidatePath(`/admin/applications/${id}`);
}
