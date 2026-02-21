import { NextResponse } from "next/server";
import { readFile } from "fs/promises";
import { join } from "path";
import { hashString } from "@/lib/utils";

// Cache the snippet content in memory after first read
let cachedContent: string | null = null;
let cachedEtag: string | null = null;

async function getSnippetContent(): Promise<string> {
  if (cachedContent !== null) return cachedContent;

  // Try reading from public/snippet.js first, then fall back to project root
  const paths = [
    join(process.cwd(), "public", "snippet.js"),
    join(process.cwd(), "..", "snippet.js"),
  ];

  for (const filePath of paths) {
    try {
      cachedContent = await readFile(filePath, "utf-8");
      cachedEtag = hashString(cachedContent).slice(0, 16);
      return cachedContent;
    } catch {
      // File not found at this path, try next
    }
  }

  throw new Error("snippet.js not found");
}

export async function GET(request: Request) {
  try {
    const content = await getSnippetContent();

    // Check If-None-Match for 304 responses
    const ifNoneMatch = request.headers.get("if-none-match");
    if (ifNoneMatch && cachedEtag && ifNoneMatch === `"${cachedEtag}"`) {
      return new NextResponse(null, {
        status: 304,
        headers: {
          ETag: `"${cachedEtag}"`,
          "Cache-Control": "public, max-age=31536000, immutable",
        },
      });
    }

    return new NextResponse(content, {
      status: 200,
      headers: {
        "Content-Type": "application/javascript; charset=utf-8",
        "Cache-Control": "public, max-age=31536000, immutable",
        ETag: `"${cachedEtag}"`,
        "Access-Control-Allow-Origin": "*",
      },
    });
  } catch {
    return new NextResponse("// snippet.js not found", {
      status: 404,
      headers: {
        "Content-Type": "application/javascript; charset=utf-8",
      },
    });
  }
}
