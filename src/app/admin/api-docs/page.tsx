"use client";

import { useState } from "react";
import { Copy, Check, BookOpen, Key, ChevronDown, ChevronRight } from "lucide-react";
import Link from "next/link";

// ─── Types ───────────────────────────────────────────────────────────────────

interface Param {
  name: string;
  in: "query" | "body" | "path";
  type: string;
  required?: boolean;
  description: string;
}

interface EndpointDef {
  method: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  path: string;
  summary: string;
  description?: string;
  params?: Param[];
  bodyExample?: string;
  responseExample?: string;
}

interface Section {
  title: string;
  description: string;
  color: string;
  endpoints: EndpointDef[];
}

// ─── Data ────────────────────────────────────────────────────────────────────

const BASE = "/api/v1";

const SECTIONS: Section[] = [
  {
    title: "Summary",
    description: "High-level dashboard statistics.",
    color: "from-blue-500 to-cyan-500",
    endpoints: [
      {
        method: "GET",
        path: `${BASE}/summary`,
        summary: "Get dashboard summary",
        description:
          "Returns aggregated stats for clicks, partners, offers, domains, rules, and applications, plus the 5 most recent click events.",
        responseExample: JSON.stringify(
          {
            summary: {
              clicks: { total: 1240, last_7_days: 87 },
              partners: { total: 12, active: 10 },
              offers: { total: 5, active: 4 },
              domains: { total: 3, active: 3 },
              rules: { total: 8, active: 7 },
              applications: { total: 34, pending: 2 },
            },
            recent_clicks: [{ _id: "…", ip_address: "1.2.3.4", redirect_url: "https://…", clicked_at: "2026-02-20T12:00:00Z" }],
          },
          null,
          2
        ),
      },
    ],
  },
  {
    title: "Click Logs",
    description: "Paginated, filterable access to redirect click events.",
    color: "from-sky-500 to-blue-500",
    endpoints: [
      {
        method: "GET",
        path: `${BASE}/clicks`,
        summary: "List click logs",
        description: "Returns paginated click log entries. Filter by domain, partner, offer, IP address, or date range.",
        params: [
          { name: "page", in: "query", type: "number", description: "Page number (default: 1)" },
          { name: "limit", in: "query", type: "number", description: "Results per page, max 200 (default: 50)" },
          { name: "domain_id", in: "query", type: "string", description: "Filter by domain ObjectId" },
          { name: "partner_id", in: "query", type: "string", description: "Filter by partner ObjectId" },
          { name: "offer_id", in: "query", type: "string", description: "Filter by offer ObjectId" },
          { name: "ip_address", in: "query", type: "string", description: "Filter by exact IP address" },
          { name: "from", in: "query", type: "ISO 8601 date", description: "Start of date range (clicked_at ≥)" },
          { name: "to", in: "query", type: "ISO 8601 date", description: "End of date range (clicked_at ≤)" },
        ],
        responseExample: JSON.stringify({ data: [{ _id: "…", ip_address: "1.2.3.4", redirect_url: "https://…", clicked_at: "2026-02-20T12:00:00Z" }], total: 1240, page: 1, limit: 50, pages: 25 }, null, 2),
      },
    ],
  },
  {
    title: "Applications",
    description: "Manage partner applications — filter and update status.",
    color: "from-amber-500 to-orange-500",
    endpoints: [
      {
        method: "GET",
        path: `${BASE}/applications`,
        summary: "List applications",
        description: "Returns paginated partner applications. Filter by status, email, or name.",
        params: [
          { name: "page", in: "query", type: "number", description: "Page number (default: 1)" },
          { name: "limit", in: "query", type: "number", description: "Results per page, max 200 (default: 50)" },
          { name: "status", in: "query", type: "string", description: "Filter: pending | approved | rejected | info_requested" },
          { name: "email", in: "query", type: "string", description: "Partial email match (case-insensitive)" },
          { name: "name", in: "query", type: "string", description: "Partial name match (case-insensitive)" },
        ],
        responseExample: JSON.stringify({ data: [{ _id: "…", name: "Alice", email: "alice@example.com", status: "pending", created_at: "2026-02-01T10:00:00Z" }], total: 34, page: 1, limit: 50, pages: 1 }, null, 2),
      },
      {
        method: "GET",
        path: `${BASE}/applications/:id`,
        summary: "Get single application",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Application ObjectId" }],
      },
      {
        method: "PATCH",
        path: `${BASE}/applications/:id`,
        summary: "Update application",
        description: "Partially update an application. Allowed fields: status, notes, domain_verification_status, domain_verified.",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Application ObjectId" }],
        bodyExample: JSON.stringify({ status: "approved", notes: "Looks good" }, null, 2),
      },
    ],
  },
  {
    title: "Domains",
    description: "Full CRUD for tracked domains.",
    color: "from-teal-500 to-emerald-500",
    endpoints: [
      {
        method: "GET",
        path: `${BASE}/domains`,
        summary: "List domains",
        params: [{ name: "is_active", in: "query", type: "boolean", description: "Filter by active status" }],
        responseExample: JSON.stringify({ data: [{ _id: "…", domain_name: "example.com", is_active: true, created_at: "2026-01-01T00:00:00Z" }], total: 3 }, null, 2),
      },
      {
        method: "POST",
        path: `${BASE}/domains`,
        summary: "Create domain",
        bodyExample: JSON.stringify({ domain_name: "example.com", is_active: true }, null, 2),
      },
      {
        method: "GET",
        path: `${BASE}/domains/:id`,
        summary: "Get domain",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Domain ObjectId" }],
      },
      {
        method: "PUT",
        path: `${BASE}/domains/:id`,
        summary: "Update domain",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Domain ObjectId" }],
        bodyExample: JSON.stringify({ domain_name: "new-domain.com", is_active: false }, null, 2),
      },
      {
        method: "DELETE",
        path: `${BASE}/domains/:id`,
        summary: "Delete domain",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Domain ObjectId" }],
        responseExample: JSON.stringify({ success: true }, null, 2),
      },
    ],
  },
  {
    title: "Partners",
    description: "Full CRUD for affiliate partners.",
    color: "from-indigo-500 to-violet-500",
    endpoints: [
      {
        method: "GET",
        path: `${BASE}/partners`,
        summary: "List partners",
        params: [
          { name: "is_active", in: "query", type: "boolean", description: "Filter by active status" },
          { name: "aff_id", in: "query", type: "string", description: "Filter by exact affiliate ID" },
        ],
        responseExample: JSON.stringify({ data: [{ _id: "…", partner_name: "Alice", aff_id: "alice123", is_active: true }], total: 12 }, null, 2),
      },
      {
        method: "POST",
        path: `${BASE}/partners`,
        summary: "Create partner",
        bodyExample: JSON.stringify({ partner_name: "Alice", aff_id: "alice123", is_active: true }, null, 2),
      },
      {
        method: "GET",
        path: `${BASE}/partners/:id`,
        summary: "Get partner",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Partner ObjectId" }],
      },
      {
        method: "PUT",
        path: `${BASE}/partners/:id`,
        summary: "Update partner",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Partner ObjectId" }],
        bodyExample: JSON.stringify({ partner_name: "Alice Updated", is_active: false }, null, 2),
      },
      {
        method: "DELETE",
        path: `${BASE}/partners/:id`,
        summary: "Delete partner",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Partner ObjectId" }],
        responseExample: JSON.stringify({ success: true }, null, 2),
      },
    ],
  },
  {
    title: "Offers",
    description: "Full CRUD for ClickBank offers.",
    color: "from-orange-500 to-rose-500",
    endpoints: [
      {
        method: "GET",
        path: `${BASE}/offers`,
        summary: "List offers",
        params: [{ name: "is_active", in: "query", type: "boolean", description: "Filter by active status" }],
        responseExample: JSON.stringify({ data: [{ _id: "…", offer_name: "My Offer", clickbank_vendor: "vendor123", clickbank_hoplink: "https://…", is_active: true }], total: 5 }, null, 2),
      },
      {
        method: "POST",
        path: `${BASE}/offers`,
        summary: "Create offer",
        bodyExample: JSON.stringify({ offer_name: "My Offer", clickbank_vendor: "vendor123", clickbank_hoplink: "https://vendor123.pay.clickbank.net/", is_active: true }, null, 2),
      },
      {
        method: "GET",
        path: `${BASE}/offers/:id`,
        summary: "Get offer",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Offer ObjectId" }],
      },
      {
        method: "PUT",
        path: `${BASE}/offers/:id`,
        summary: "Update offer",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Offer ObjectId" }],
        bodyExample: JSON.stringify({ offer_name: "Updated Offer Name", is_active: false }, null, 2),
      },
      {
        method: "DELETE",
        path: `${BASE}/offers/:id`,
        summary: "Delete offer",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Offer ObjectId" }],
        responseExample: JSON.stringify({ success: true }, null, 2),
      },
    ],
  },
  {
    title: "Redirect Rules",
    description: "Full CRUD for redirect routing rules.",
    color: "from-pink-500 to-rose-500",
    endpoints: [
      {
        method: "GET",
        path: `${BASE}/rules`,
        summary: "List redirect rules",
        params: [
          { name: "rule_type", in: "query", type: "string", description: "Filter: global | domain | partner" },
          { name: "is_paused", in: "query", type: "boolean", description: "Filter by paused status" },
        ],
        responseExample: JSON.stringify({ data: [{ _id: "…", rule_name: "Global Rule", rule_type: "global", is_paused: false, priority: 0 }], total: 8 }, null, 2),
      },
      {
        method: "POST",
        path: `${BASE}/rules`,
        summary: "Create redirect rule",
        bodyExample: JSON.stringify({ rule_name: "My Rule", rule_type: "partner", offer_id: "<offer_id>", partner_id: "<partner_id>", is_paused: false, priority: 1 }, null, 2),
      },
      {
        method: "GET",
        path: `${BASE}/rules/:id`,
        summary: "Get redirect rule",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Rule ObjectId" }],
      },
      {
        method: "PUT",
        path: `${BASE}/rules/:id`,
        summary: "Update redirect rule",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Rule ObjectId" }],
        bodyExample: JSON.stringify({ is_paused: true, priority: 2 }, null, 2),
      },
      {
        method: "DELETE",
        path: `${BASE}/rules/:id`,
        summary: "Delete redirect rule",
        params: [{ name: "id", in: "path", type: "string", required: true, description: "Rule ObjectId" }],
        responseExample: JSON.stringify({ success: true }, null, 2),
      },
    ],
  },
];

// ─── Markdown builder ─────────────────────────────────────────────────────────

function buildMarkdown(): string {
  const lines: string[] = [
    "# ClickBank Partner Manager — API Reference",
    "",
    "## Authentication",
    "",
    "All endpoints require a Bearer API key in the `Authorization` header:",
    "",
    "```",
    "Authorization: Bearer cbpm_<your_key>",
    "```",
    "",
    "Base URL: `/api/v1`",
    "",
    "---",
    "",
  ];

  for (const section of SECTIONS) {
    lines.push(`## ${section.title}`, "", section.description, "");

    for (const ep of section.endpoints) {
      lines.push(`### ${ep.method} ${ep.path}`, "", `**${ep.summary}**`, "");
      if (ep.description) lines.push(ep.description, "");
      if (ep.params && ep.params.length > 0) {
        lines.push("**Parameters:**", "");
        lines.push("| Name | In | Type | Required | Description |");
        lines.push("|------|----|------|----------|-------------|");
        for (const p of ep.params) {
          lines.push(`| \`${p.name}\` | ${p.in} | \`${p.type}\` | ${p.required ? "Yes" : "No"} | ${p.description} |`);
        }
        lines.push("");
      }
      if (ep.bodyExample) {
        lines.push("**Request Body (JSON):**", "", "```json", ep.bodyExample, "```", "");
      }
      if (ep.responseExample) {
        lines.push("**Response:**", "", "```json", ep.responseExample, "```", "");
      }
      lines.push("---", "");
    }
  }

  return lines.join("\n");
}

function buildEndpointMarkdown(ep: EndpointDef): string {
  const lines: string[] = [
    `### ${ep.method} ${ep.path}`,
    "",
    `**${ep.summary}**`,
    "",
  ];
  if (ep.description) lines.push(ep.description, "");
  if (ep.params?.length) {
    lines.push("**Parameters:**", "");
    lines.push("| Name | In | Type | Required | Description |");
    lines.push("|------|----|------|----------|-------------|");
    for (const p of ep.params) {
      lines.push(`| \`${p.name}\` | ${p.in} | \`${p.type}\` | ${p.required ? "Yes" : "No"} | ${p.description} |`);
    }
    lines.push("");
  }
  if (ep.bodyExample) lines.push("**Request Body:**", "", "```json", ep.bodyExample, "```", "");
  if (ep.responseExample) lines.push("**Response:**", "", "```json", ep.responseExample, "```", "");
  return lines.join("\n");
}

// ─── Method Badge ─────────────────────────────────────────────────────────────

const METHOD_COLORS: Record<string, string> = {
  GET: "bg-blue-500/20 text-blue-400 border-blue-500/30",
  POST: "bg-emerald-500/20 text-emerald-400 border-emerald-500/30",
  PUT: "bg-amber-500/20 text-amber-400 border-amber-500/30",
  PATCH: "bg-sky-500/20 text-sky-400 border-sky-500/30",
  DELETE: "bg-rose-500/20 text-rose-400 border-rose-500/30",
};

function MethodBadge({ method }: { method: string }) {
  return (
    <span
      className={`inline-flex items-center rounded border px-2 py-0.5 text-xs font-mono font-semibold ${METHOD_COLORS[method] ?? "bg-muted text-muted-foreground border-border"}`}
    >
      {method}
    </span>
  );
}

// ─── CopyButton ───────────────────────────────────────────────────────────────

function CopyButton({ text, label = "Copy" }: { text: string; label?: string }) {
  const [copied, setCopied] = useState(false);

  function handleCopy() {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  return (
    <button
      onClick={handleCopy}
      className="inline-flex items-center gap-1.5 rounded-md border border-border bg-muted/50 hover:bg-muted px-2.5 py-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
    >
      {copied ? <Check className="h-3.5 w-3.5 text-emerald-400" /> : <Copy className="h-3.5 w-3.5" />}
      {copied ? "Copied!" : label}
    </button>
  );
}

// ─── EndpointCard ─────────────────────────────────────────────────────────────

function EndpointCard({ ep }: { ep: EndpointDef }) {
  const [open, setOpen] = useState(false);

  return (
    <div className="rounded-xl border border-border bg-card overflow-hidden">
      {/* Header row */}
      <div
        className="flex items-center gap-3 px-4 py-3 cursor-pointer select-none hover:bg-muted/40 transition-colors"
        onClick={() => setOpen((o) => !o)}
      >
        <MethodBadge method={ep.method} />
        <code className="flex-1 text-sm font-mono text-foreground truncate">{ep.path}</code>
        <span className="text-sm text-muted-foreground hidden sm:block">{ep.summary}</span>
        <div className="flex items-center gap-2 ml-auto shrink-0">
          <CopyButton text={buildEndpointMarkdown(ep)} label="Copy" />
          {open ? (
            <ChevronDown className="h-4 w-4 text-muted-foreground" />
          ) : (
            <ChevronRight className="h-4 w-4 text-muted-foreground" />
          )}
        </div>
      </div>

      {/* Expanded details */}
      {open && (
        <div className="border-t border-border px-5 py-4 space-y-4 text-sm">
          {ep.description && <p className="text-muted-foreground">{ep.description}</p>}

          {ep.params && ep.params.length > 0 && (
            <div>
              <p className="text-xs font-semibold text-foreground uppercase tracking-wider mb-2">Parameters</p>
              <div className="overflow-x-auto">
                <table className="w-full text-xs">
                  <thead>
                    <tr className="text-left border-b border-border">
                      <th className="pr-4 pb-2 font-medium text-muted-foreground">Name</th>
                      <th className="pr-4 pb-2 font-medium text-muted-foreground">In</th>
                      <th className="pr-4 pb-2 font-medium text-muted-foreground">Type</th>
                      <th className="pr-4 pb-2 font-medium text-muted-foreground">Req?</th>
                      <th className="pb-2 font-medium text-muted-foreground">Description</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border/50">
                    {ep.params.map((p) => (
                      <tr key={p.name}>
                        <td className="pr-4 py-1.5">
                          <code className="rounded bg-muted px-1 py-0.5 font-mono text-foreground">{p.name}</code>
                        </td>
                        <td className="pr-4 py-1.5 text-muted-foreground">{p.in}</td>
                        <td className="pr-4 py-1.5">
                          <code className="rounded bg-muted px-1 py-0.5 font-mono text-muted-foreground">{p.type}</code>
                        </td>
                        <td className="pr-4 py-1.5 text-muted-foreground">
                          {p.required ? (
                            <span className="text-rose-400">yes</span>
                          ) : (
                            <span className="text-muted-foreground/60">no</span>
                          )}
                        </td>
                        <td className="py-1.5 text-muted-foreground">{p.description}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {ep.bodyExample && (
            <div>
              <p className="text-xs font-semibold text-foreground uppercase tracking-wider mb-2">Request Body</p>
              <pre className="rounded-lg bg-[#0d0d0d] border border-border text-emerald-300 text-xs font-mono p-4 overflow-x-auto whitespace-pre-wrap">
                {ep.bodyExample}
              </pre>
            </div>
          )}

          {ep.responseExample && (
            <div>
              <p className="text-xs font-semibold text-foreground uppercase tracking-wider mb-2">Response</p>
              <pre className="rounded-lg bg-[#0d0d0d] border border-border text-sky-300 text-xs font-mono p-4 overflow-x-auto whitespace-pre-wrap">
                {ep.responseExample}
              </pre>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function ApiDocsPage() {
  const fullMarkdown = buildMarkdown();
  const [copied, setCopied] = useState(false);

  function copyForAI() {
    navigator.clipboard.writeText(fullMarkdown);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  return (
    <div className="space-y-8 animate-in max-w-4xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-violet-600 text-white shadow-sm">
            <BookOpen className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-foreground">API Documentation</h1>
            <p className="text-sm text-muted-foreground">
              REST API for AI agents and external integrations
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={copyForAI}
            className="inline-flex items-center gap-2 rounded-lg border border-violet-500/40 bg-violet-500/15 hover:bg-violet-500/25 text-violet-300 hover:text-violet-200 px-4 py-2 text-sm font-medium transition-colors"
          >
            {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
            {copied ? "Copied!" : "Copy All for AI"}
          </button>
          <Link
            href="/admin/api-keys"
            className="inline-flex items-center gap-2 rounded-lg border border-border bg-card hover:bg-muted px-4 py-2 text-sm font-medium text-foreground transition-colors"
          >
            <Key className="h-4 w-4" />
            Manage Keys
          </Link>
        </div>
      </div>

      {/* Authentication */}
      <div className="rounded-xl border border-violet-500/30 bg-violet-500/10 p-5">
        <h2 className="text-base font-semibold text-violet-300 mb-3 flex items-center gap-2">
          <Key className="h-4 w-4" />
          Authentication
        </h2>
        <p className="text-sm text-violet-200/80 mb-3">
          Every request must include your API key as a <strong>Bearer</strong> token in the{" "}
          <code className="rounded bg-violet-900/40 px-1 py-0.5 font-mono text-xs">Authorization</code> header.
        </p>
        <pre className="rounded-lg bg-[#0d0d0d] border border-violet-500/20 text-violet-300 text-xs font-mono p-4">
          {`Authorization: Bearer cbpm_<your_api_key>
Content-Type: application/json`}
        </pre>
        <p className="text-sm text-violet-200/70 mt-3">
          Base URL: <code className="rounded bg-violet-900/40 px-1.5 py-0.5 font-mono text-xs">/api/v1</code>
          &nbsp;·&nbsp; All responses are JSON &nbsp;·&nbsp; Errors include an <code className="font-mono text-xs">error</code> field
        </p>
      </div>

      {/* Error codes */}
      <div className="rounded-xl bg-card border border-border p-5">
        <h2 className="text-base font-semibold text-foreground mb-3">HTTP Status Codes</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
          {[
            ["200", "OK — request succeeded"],
            ["201", "Created — resource created"],
            ["400", "Bad Request — missing/invalid fields"],
            ["401", "Unauthorized — missing or invalid API key"],
            ["404", "Not Found — resource doesn't exist"],
            ["500", "Internal Server Error"],
          ].map(([code, desc]) => (
            <div key={code} className="flex items-baseline gap-2">
              <code className="rounded bg-muted px-1.5 py-0.5 text-xs font-mono font-semibold text-foreground shrink-0">
                {code}
              </code>
              <span className="text-muted-foreground text-xs">{desc}</span>
            </div>
          ))}
        </div>
      </div>

      {/* Sections */}
      {SECTIONS.map((section) => (
        <div key={section.title} className="space-y-3">
          {/* Section Header */}
          <div className="flex items-center gap-3">
            <div className={`h-1 w-1 rounded-full bg-gradient-to-r ${section.color}`} />
            <h2 className="text-lg font-bold text-foreground">{section.title}</h2>
            <span className="text-sm text-muted-foreground">{section.description}</span>
          </div>

          {/* Endpoints */}
          <div className="space-y-2">
            {section.endpoints.map((ep) => (
              <EndpointCard key={`${ep.method}-${ep.path}`} ep={ep} />
            ))}
          </div>
        </div>
      ))}

      {/* Footer note */}
      <div className="rounded-xl border border-border bg-card/50 p-5 text-sm text-muted-foreground">
        <p>
          <strong className="text-foreground">Tip for AI agents:</strong> Click{" "}
          <span className="text-violet-400 font-medium">Copy All for AI</span> to get the entire documentation as
          Markdown. Paste it into your agent&apos;s system prompt so it can understand and call every endpoint.
        </p>
      </div>
    </div>
  );
}
