"use client";

import { useState, useTransition } from "react";
import { createApiKey, revokeApiKey, deleteApiKey } from "./actions";
import { Key, Plus, Copy, Check, Trash2, Ban, Loader2 } from "lucide-react";

interface ApiKey {
  _id: string;
  name: string;
  key_prefix: string;
  is_active: boolean;
  last_used_at?: string | null;
  created_at: string;
}

interface Props {
  initialKeys: ApiKey[];
}

export function ApiKeyManager({ initialKeys }: Props) {
  const [keys, setKeys] = useState(initialKeys);
  const [name, setName] = useState("");
  const [newKey, setNewKey] = useState<{ raw: string; name: string } | null>(null);
  const [copied, setCopied] = useState(false);
  const [error, setError] = useState("");
  const [isPending, startTransition] = useTransition();

  async function handleCreate(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    const fd = new FormData();
    fd.set("name", name);
    const result = await createApiKey(fd);
    if (!result.success) {
      setError(result.error);
      return;
    }
    setNewKey({ raw: result.rawKey, name: result.name });
    setName("");
    // optimistically add a partial key row (will be refreshed on next render)
    setKeys((prev) => [
      {
        _id: Math.random().toString(),
        name: result.name,
        key_prefix: result.rawKey.slice(0, 12),
        is_active: true,
        last_used_at: null,
        created_at: new Date().toISOString(),
      },
      ...prev,
    ]);
  }

  function copyKey() {
    if (!newKey) return;
    navigator.clipboard.writeText(newKey.raw);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  function handleRevoke(id: string) {
    startTransition(async () => {
      await revokeApiKey(id);
      setKeys((prev) => prev.map((k) => (k._id === id ? { ...k, is_active: false } : k)));
    });
  }

  function handleDelete(id: string) {
    if (!confirm("Delete this API key? This cannot be undone.")) return;
    startTransition(async () => {
      await deleteApiKey(id);
      setKeys((prev) => prev.filter((k) => k._id !== id));
    });
  }

  return (
    <div className="space-y-6">
      {/* New Key Banner */}
      {newKey && (
        <div className="rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-5">
          <div className="flex items-start gap-3">
            <Key className="h-5 w-5 text-emerald-400 mt-0.5 shrink-0" />
            <div className="flex-1 min-w-0">
              <p className="font-semibold text-emerald-300 text-sm mb-1">
                API key created — copy it now, it won&apos;t be shown again
              </p>
              <div className="flex items-center gap-2 mt-2">
                <code className="flex-1 rounded-lg bg-black/30 border border-emerald-500/30 px-3 py-2 text-sm font-mono text-emerald-200 break-all">
                  {newKey.raw}
                </code>
                <button
                  onClick={copyKey}
                  className="shrink-0 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 text-sm font-medium transition-colors flex items-center gap-1.5"
                >
                  {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                  {copied ? "Copied" : "Copy"}
                </button>
              </div>
            </div>
          </div>
          <button
            onClick={() => setNewKey(null)}
            className="mt-3 text-xs text-emerald-400/70 hover:text-emerald-400 underline"
          >
            I&apos;ve saved my key — dismiss
          </button>
        </div>
      )}

      {/* Create Form */}
      <div className="rounded-xl bg-card border border-border p-5">
        <h2 className="text-base font-semibold text-foreground mb-4 flex items-center gap-2">
          <Plus className="h-4 w-4" />
          Generate New API Key
        </h2>
        <form onSubmit={handleCreate} className="flex gap-3 items-end flex-wrap">
          <div className="flex-1 min-w-48">
            <label className="block text-xs font-medium text-muted-foreground mb-1.5">
              Key Name
            </label>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="e.g. My AI Agent"
              required
              className="w-full rounded-lg bg-muted border border-border px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
          </div>
          <button
            type="submit"
            disabled={isPending}
            className="inline-flex items-center gap-2 rounded-lg bg-primary hover:bg-primary/90 disabled:opacity-50 text-primary-foreground px-4 py-2 text-sm font-medium transition-colors"
          >
            {isPending ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <Key className="h-4 w-4" />
            )}
            Generate Key
          </button>
        </form>
        {error && <p className="mt-2 text-sm text-destructive">{error}</p>}
      </div>

      {/* Keys Table */}
      <div className="rounded-xl bg-card border border-border overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-muted/50 text-left border-b border-border">
                <th className="px-5 py-3 font-medium text-muted-foreground">Name</th>
                <th className="px-5 py-3 font-medium text-muted-foreground">Key Prefix</th>
                <th className="px-5 py-3 font-medium text-muted-foreground">Status</th>
                <th className="px-5 py-3 font-medium text-muted-foreground">Last Used</th>
                <th className="px-5 py-3 font-medium text-muted-foreground">Created</th>
                <th className="px-5 py-3 font-medium text-muted-foreground text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {keys.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-5 py-10 text-center text-muted-foreground">
                    No API keys yet. Generate one above.
                  </td>
                </tr>
              )}
              {keys.map((k) => (
                <tr key={k._id} className="hover:bg-muted/30 transition-colors">
                  <td className="px-5 py-3 font-medium text-foreground">{k.name}</td>
                  <td className="px-5 py-3">
                    <code className="rounded bg-muted px-1.5 py-0.5 text-xs font-mono text-muted-foreground">
                      {k.key_prefix}…
                    </code>
                  </td>
                  <td className="px-5 py-3">
                    <span
                      className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                        k.is_active
                          ? "bg-emerald-500/15 text-emerald-400"
                          : "bg-destructive/15 text-destructive"
                      }`}
                    >
                      {k.is_active ? "Active" : "Revoked"}
                    </span>
                  </td>
                  <td className="px-5 py-3 text-muted-foreground">
                    {k.last_used_at
                      ? new Date(k.last_used_at).toLocaleString()
                      : "Never"}
                  </td>
                  <td className="px-5 py-3 text-muted-foreground">
                    {new Date(k.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-5 py-3 text-right">
                    <div className="flex items-center justify-end gap-1.5">
                      {k.is_active && (
                        <button
                          onClick={() => handleRevoke(k._id)}
                          disabled={isPending}
                          title="Revoke key"
                          className="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-amber-400 hover:bg-amber-400/10 transition-colors disabled:opacity-50"
                        >
                          <Ban className="h-3.5 w-3.5" />
                          Revoke
                        </button>
                      )}
                      <button
                        onClick={() => handleDelete(k._id)}
                        disabled={isPending}
                        title="Delete key"
                        className="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-destructive hover:bg-destructive/10 transition-colors disabled:opacity-50"
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
