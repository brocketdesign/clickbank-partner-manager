import { auth } from "@/lib/auth";
import { NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";

export async function GET() {
  const session = await auth();
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const db = await getDb();
  const count = await db
    .collection("partner_applications")
    .countDocuments({ status: "pending" });

  return NextResponse.json({ count });
}
