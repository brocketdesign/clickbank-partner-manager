"use server";

import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

export async function savePartner(formData: FormData) {
  const db = await getDb();
  const id = formData.get("id") as string | null;
  const affId = (formData.get("aff_id") as string)?.trim() ?? "";
  const partnerName = (formData.get("partner_name") as string)?.trim() ?? "";
  const isActive =
    formData.get("is_active") === "on" || formData.get("is_active") === "true";

  if (!affId) {
    throw new Error("Affiliate ID is required");
  }

  if (!partnerName) {
    throw new Error("Partner name is required");
  }

  // Check for duplicate aff_id
  const existing = await db.collection("partners").findOne({ aff_id: affId });

  if (existing && (!id || existing._id.toString() !== id)) {
    throw new Error("A partner with this Affiliate ID already exists");
  }

  if (id) {
    // Update existing
    await db.collection("partners").updateOne(
      { _id: new ObjectId(id) },
      {
        $set: {
          aff_id: affId,
          partner_name: partnerName,
          is_active: isActive,
          updated_at: new Date(),
        },
      }
    );
  } else {
    // Insert new
    await db.collection("partners").insertOne({
      aff_id: affId,
      partner_name: partnerName,
      is_active: isActive,
      created_at: new Date(),
      updated_at: new Date(),
    });
  }

  revalidatePath("/admin/partners");
  redirect("/admin/partners");
}

export async function togglePartner(id: string) {
  const db = await getDb();
  const partner = await db
    .collection("partners")
    .findOne({ _id: new ObjectId(id) });

  if (!partner) {
    throw new Error("Partner not found");
  }

  await db.collection("partners").updateOne(
    { _id: new ObjectId(id) },
    {
      $set: {
        is_active: !partner.is_active,
        updated_at: new Date(),
      },
    }
  );

  revalidatePath("/admin/partners");
}

export async function deletePartner(id: string) {
  const db = await getDb();

  // Delete the partner
  await db.collection("partners").deleteOne({ _id: new ObjectId(id) });

  // Delete any redirect_rules referencing this partner_id
  await db
    .collection("redirect_rules")
    .deleteMany({ partner_id: new ObjectId(id) });

  revalidatePath("/admin/partners");
}
