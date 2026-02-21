import { hash } from "bcryptjs";
import { MongoClient } from "mongodb";

// Seed script — run with: npm run seed
async function seed() {
  const uri = process.env.MONGODB_URI || "mongodb://localhost:27017/clickbank_partner_manager";
  const client = new MongoClient(uri);

  try {
    await client.connect();
    const db = client.db();
    console.log("Connected to MongoDB");

    // Create collections with indexes
    const collections = await db.listCollections().toArray();
    const existing = collections.map((c) => c.name);

    // Admin Users
    if (!existing.includes("admin_users")) {
      await db.createCollection("admin_users");
    }
    await db.collection("admin_users").createIndex({ username: 1 }, { unique: true });

    // Check if admin user exists
    const adminExists = await db.collection("admin_users").findOne({ username: "admin" });
    if (!adminExists) {
      const password_hash = await hash("admin123", 12);
      await db.collection("admin_users").insertOne({
        username: "admin",
        password_hash,
        created_at: new Date(),
      });
      console.log("Created admin user (admin / admin123)");
    }

    // Domains
    if (!existing.includes("domains")) {
      await db.createCollection("domains");
    }
    await db.collection("domains").createIndex({ domain_name: 1 }, { unique: true });
    await db.collection("domains").createIndex({ is_active: 1 });

    // Partners
    if (!existing.includes("partners")) {
      await db.createCollection("partners");
    }
    await db.collection("partners").createIndex({ aff_id: 1 }, { unique: true });
    await db.collection("partners").createIndex({ is_active: 1 });

    // Offers
    if (!existing.includes("offers")) {
      await db.createCollection("offers");
    }
    await db.collection("offers").createIndex({ is_active: 1 });

    // Redirect Rules
    if (!existing.includes("redirect_rules")) {
      await db.createCollection("redirect_rules");
    }
    await db.collection("redirect_rules").createIndex({ rule_type: 1 });
    await db.collection("redirect_rules").createIndex({ domain_id: 1 });
    await db.collection("redirect_rules").createIndex({ partner_id: 1 });
    await db.collection("redirect_rules").createIndex({ priority: 1 });
    await db.collection("redirect_rules").createIndex({ is_paused: 1 });

    // Partner Applications
    if (!existing.includes("partner_applications")) {
      await db.createCollection("partner_applications");
    }
    await db.collection("partner_applications").createIndex({ status: 1 });
    await db.collection("partner_applications").createIndex({ email: 1 });
    await db.collection("partner_applications").createIndex({ created_at: -1 });

    // Partners New
    if (!existing.includes("partners_new")) {
      await db.createCollection("partners_new");
    }
    await db.collection("partners_new").createIndex({ partner_id_public: 1 }, { unique: true });
    await db.collection("partners_new").createIndex({ email: 1 }, { unique: true });
    await db.collection("partners_new").createIndex({ status: 1 });

    // Creatives
    if (!existing.includes("creatives")) {
      await db.createCollection("creatives");
    }
    await db.collection("creatives").createIndex({ partner_id: 1 });
    await db.collection("creatives").createIndex({ active: 1 });

    // Click Logs
    if (!existing.includes("click_logs")) {
      await db.createCollection("click_logs");
    }
    await db.collection("click_logs").createIndex({ clicked_at: -1 });
    await db.collection("click_logs").createIndex({ domain_id: 1 });
    await db.collection("click_logs").createIndex({ partner_id: 1 });
    await db.collection("click_logs").createIndex({ offer_id: 1 });

    // Clicks (attribution)
    if (!existing.includes("clicks")) {
      await db.createCollection("clicks");
    }
    await db.collection("clicks").createIndex({ click_id: 1 }, { unique: true });
    await db.collection("clicks").createIndex({ partner_id: 1 });
    await db.collection("clicks").createIndex({ ts: -1 });

    // Impressions
    if (!existing.includes("impressions")) {
      await db.createCollection("impressions");
    }
    await db.collection("impressions").createIndex({ partner_id: 1 });
    await db.collection("impressions").createIndex({ ts: -1 });

    // Conversions
    if (!existing.includes("conversions")) {
      await db.createCollection("conversions");
    }
    await db.collection("conversions").createIndex({ click_id: 1 });
    await db.collection("conversions").createIndex({ ts: -1 });

    // Payouts
    if (!existing.includes("payouts")) {
      await db.createCollection("payouts");
    }
    await db.collection("payouts").createIndex({ partner_id: 1 });
    await db.collection("payouts").createIndex({ month: 1 });
    await db.collection("payouts").createIndex(
      { partner_id: 1, month: 1 },
      { unique: true }
    );

    console.log("All collections and indexes created successfully!");
  } catch (err) {
    console.error("Seed error:", err);
    process.exit(1);
  } finally {
    await client.close();
  }
}

seed();
