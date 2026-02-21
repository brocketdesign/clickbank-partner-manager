"use server";

import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

export async function saveDomain(formData: FormData) {
  const db = await getDb();
  const id = formData.get("id") as string | null;
  let domainName = (formData.get("domain_name") as string)?.trim() ?? "";
  const isActive = formData.get("is_active") === "on" || formData.get("is_active") === "true";

  // Strip http:// and https://
  domainName = domainName.replace(/^https?:\/\//, "").replace(/\/+$/, "");

  if (!domainName) {
    throw new Error("Domain name is required");
  }

  if (id) {
    // Update existing
    await db.collection("domains").updateOne(
      { _id: new ObjectId(id) },
      {
        $set: {
          domain_name: domainName,
          is_active: isActive,
          updated_at: new Date(),
        },
      }
    );
  } else {
    // Insert new
    await db.collection("domains").insertOne({
      domain_name: domainName,
      is_active: isActive,
      created_at: new Date(),
      updated_at: new Date(),
    });
  }

  revalidatePath("/admin/domains");
  redirect("/admin/domains");
}

export async function toggleDomain(id: string) {
  const db = await getDb();
  const domain = await db.collection("domains").findOne({ _id: new ObjectId(id) });

  if (!domain) {
    throw new Error("Domain not found");
  }

  await db.collection("domains").updateOne(
    { _id: new ObjectId(id) },
    {
      $set: {
        is_active: !domain.is_active,
        updated_at: new Date(),
      },
    }
  );

  revalidatePath("/admin/domains");
}

export async function deleteDomain(id: string) {
  const db = await getDb();

  // Delete the domain
  await db.collection("domains").deleteOne({ _id: new ObjectId(id) });

  // Delete any redirect_rules referencing this domain_id
  await db.collection("redirect_rules").deleteMany({ domain_id: new ObjectId(id) });

  revalidatePath("/admin/domains");
}
