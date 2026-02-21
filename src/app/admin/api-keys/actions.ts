"use server";

import { getDb } from "@/lib/mongodb";
import { generateApiKey, hashKey } from "@/lib/apiAuth";
import { ObjectId } from "mongodb";
import { revalidatePath } from "next/cache";

export type CreateApiKeyResult =
  | { success: true; rawKey: string; name: string }
  | { success: false; error: string };

export async function createApiKey(formData: FormData): Promise<CreateApiKeyResult> {
  const name = (formData.get("name") as string)?.trim();
  if (!name) return { success: false, error: "Key name is required" };

  const rawKey = generateApiKey();
  const db = await getDb();
  await db.collection("api_keys").insertOne({
    name,
    key_hash: hashKey(rawKey),
    key_prefix: rawKey.slice(0, 12),
    is_active: true,
    created_at: new Date(),
  });

  revalidatePath("/admin/api-keys");
  return { success: true, rawKey, name };
}

export async function revokeApiKey(id: string) {
  const db = await getDb();
  await db
    .collection("api_keys")
    .updateOne({ _id: new ObjectId(id) }, { $set: { is_active: false } });
  revalidatePath("/admin/api-keys");
}

export async function deleteApiKey(id: string) {
  const db = await getDb();
  await db.collection("api_keys").deleteOne({ _id: new ObjectId(id) });
  revalidatePath("/admin/api-keys");
}
