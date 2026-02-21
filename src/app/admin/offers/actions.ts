"use server";

import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

export async function saveOffer(formData: FormData) {
  const db = await getDb();
  const id = formData.get("id") as string | null;
  const offerName = (formData.get("offer_name") as string)?.trim() ?? "";
  const clickbankVendor =
    (formData.get("clickbank_vendor") as string)?.trim() ?? "";
  const clickbankHoplink =
    (formData.get("clickbank_hoplink") as string)?.trim() ?? "";
  const isActive =
    formData.get("is_active") === "on" || formData.get("is_active") === "true";

  if (!offerName) {
    throw new Error("Offer name is required");
  }

  if (!clickbankVendor) {
    throw new Error("ClickBank vendor is required");
  }

  if (!clickbankHoplink) {
    throw new Error("ClickBank hoplink is required");
  }

  if (id) {
    // Update existing
    await db.collection("offers").updateOne(
      { _id: new ObjectId(id) },
      {
        $set: {
          offer_name: offerName,
          clickbank_vendor: clickbankVendor,
          clickbank_hoplink: clickbankHoplink,
          is_active: isActive,
          updated_at: new Date(),
        },
      }
    );
  } else {
    // Insert new
    await db.collection("offers").insertOne({
      offer_name: offerName,
      clickbank_vendor: clickbankVendor,
      clickbank_hoplink: clickbankHoplink,
      is_active: isActive,
      created_at: new Date(),
      updated_at: new Date(),
    });
  }

  revalidatePath("/admin/offers");
  redirect("/admin/offers");
}

export async function toggleOffer(id: string) {
  const db = await getDb();
  const offer = await db
    .collection("offers")
    .findOne({ _id: new ObjectId(id) });

  if (!offer) {
    throw new Error("Offer not found");
  }

  await db.collection("offers").updateOne(
    { _id: new ObjectId(id) },
    {
      $set: {
        is_active: !offer.is_active,
        updated_at: new Date(),
      },
    }
  );

  revalidatePath("/admin/offers");
}

export async function deleteOffer(id: string) {
  const db = await getDb();

  // Delete the offer
  await db.collection("offers").deleteOne({ _id: new ObjectId(id) });

  // Cascade delete any redirect_rules referencing this offer_id
  await db
    .collection("redirect_rules")
    .deleteMany({ offer_id: new ObjectId(id) });

  revalidatePath("/admin/offers");
}
