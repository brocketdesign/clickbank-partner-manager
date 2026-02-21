import { getDb } from "@/lib/mongodb";
import { Key, BookOpen } from "lucide-react";
import Link from "next/link";
import { ApiKeyManager } from "./ApiKeyManager";
import { WithId, Document } from "mongodb";

export default async function ApiKeysPage() {
  const db = await getDb();
  const rawKeys = await db
    .collection("api_keys")
    .find()
    .sort({ created_at: -1 })
    .toArray();

  const keys = (rawKeys as WithId<Document>[]).map((k) => ({
    _id: k._id.toString(),
    name: k.name as string,
    key_prefix: k.key_prefix as string,
    is_active: k.is_active as boolean,
    last_used_at: k.last_used_at ? (k.last_used_at as Date).toISOString() : null,
    created_at: (k.created_at as Date).toISOString(),
  }));

  return (
    <div className="space-y-6 animate-in">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-sm">
            <Key className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-foreground">API Keys</h1>
            <p className="text-sm text-muted-foreground">
              {keys.length} key{keys.length !== 1 ? "s" : ""} — use these to authenticate AI agents
            </p>
          </div>
        </div>
        <Link
          href="/admin/api-docs"
          className="inline-flex items-center gap-2 rounded-lg border border-border bg-card hover:bg-muted px-4 py-2 text-sm font-medium text-foreground transition-colors"
        >
          <BookOpen className="h-4 w-4" />
          View API Docs
        </Link>
      </div>

      {/* Info Banner */}
      <div className="rounded-xl border border-blue-500/30 bg-blue-500/10 p-4 text-sm text-blue-300">
        <p className="font-medium mb-1">Using API keys with your AI agent</p>
        <p className="text-blue-300/80">
          Pass your API key as a Bearer token in the{" "}
          <code className="rounded bg-blue-900/40 px-1 py-0.5 font-mono text-xs">
            Authorization
          </code>{" "}
          header:{" "}
          <code className="rounded bg-blue-900/40 px-1.5 py-0.5 font-mono text-xs">
            Authorization: Bearer cbpm_…
          </code>
          . The base URL is{" "}
          <code className="rounded bg-blue-900/40 px-1 py-0.5 font-mono text-xs">
            /api/v1
          </code>
          .
        </p>
      </div>

      <ApiKeyManager initialKeys={keys} />
    </div>
  );
}
