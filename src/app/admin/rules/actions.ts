"use server";

import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

export async function saveRule(formData: FormData) {
  const db = await getDb();
  const id = formData.get("id") as string | null;
  const ruleName = (formData.get("rule_name") as string)?.trim() ?? "";
  const ruleType = (formData.get("rule_type") as string) ?? "global";
  const domainIdRaw = formData.get("domain_id") as string | null;
  const partnerIdRaw = formData.get("partner_id") as string | null;
  const offerIdRaw = formData.get("offer_id") as string | null;
  const priority = parseInt(formData.get("priority") as string, 10) || 100;
  const isPaused =
    formData.get("is_paused") === "on" || formData.get("is_paused") === "true";

  if (!ruleName) {
    throw new Error("Rule name is required");
  }

  if (!offerIdRaw) {
    throw new Error("Offer is required");
  }

  const domainId =
    ruleType === "domain" && domainIdRaw && ObjectId.isValid(domainIdRaw)
      ? new ObjectId(domainIdRaw)
      : null;

  const partnerId =
    ruleType === "partner" && partnerIdRaw && ObjectId.isValid(partnerIdRaw)
      ? new ObjectId(partnerIdRaw)
      : null;

  const offerId = new ObjectId(offerIdRaw);

  const doc = {
    rule_name: ruleName,
    rule_type: ruleType,
    domain_id: domainId,
    partner_id: partnerId,
    offer_id: offerId,
    priority,
    is_paused: isPaused,
    updated_at: new Date(),
  };

  if (id) {
    await db.collection("redirect_rules").updateOne(
      { _id: new ObjectId(id) },
      { $set: doc }
    );
  } else {
    await db.collection("redirect_rules").insertOne({
      ...doc,
      created_at: new Date(),
    });
  }

  revalidatePath("/admin/rules");
  redirect("/admin/rules");
}

export async function togglePauseRule(id: string) {
  const db = await getDb();
  const rule = await db
    .collection("redirect_rules")
    .findOne({ _id: new ObjectId(id) });

  if (!rule) {
    throw new Error("Rule not found");
  }

  await db.collection("redirect_rules").updateOne(
    { _id: new ObjectId(id) },
    {
      $set: {
        is_paused: !rule.is_paused,
        updated_at: new Date(),
      },
    }
  );

  revalidatePath("/admin/rules");
}

export async function deleteRule(id: string) {
  const db = await getDb();
  await db.collection("redirect_rules").deleteOne({ _id: new ObjectId(id) });
  revalidatePath("/admin/rules");
}
