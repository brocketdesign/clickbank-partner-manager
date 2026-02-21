import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";
import {
  sanitize,
  getClientIP,
  isDisposableEmail,
  verifyDomainReachable,
  generateUUID,
} from "@/lib/utils";
import {
  sendEmail,
  applicationConfirmationEmail,
  applicationAdminNotificationEmail,
} from "@/lib/email";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const {
      name,
      email,
      blog_url,
      traffic_estimate,
      notes,
      consent,
      captcha_a,
      captcha_b,
      captcha_result,
    } = body;

    // ── 1. Required fields ──
    if (!name || !email || !blog_url) {
      return NextResponse.json(
        { error: "Name, email, and blog URL are required." },
        { status: 400 }
      );
    }

    // ── 2. Email format ──
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return NextResponse.json(
        { error: "Please enter a valid email address." },
        { status: 400 }
      );
    }

    // ── 3. Consent ──
    if (!consent) {
      return NextResponse.json(
        { error: "You must agree to the terms to submit your application." },
        { status: 400 }
      );
    }

    // ── 4. CAPTCHA ──
    if (
      captcha_a == null ||
      captcha_b == null ||
      captcha_result == null ||
      Number(captcha_a) + Number(captcha_b) !== Number(captcha_result)
    ) {
      return NextResponse.json(
        { error: "Incorrect CAPTCHA answer. Please try again." },
        { status: 400 }
      );
    }

    // ── 5. Disposable email ──
    if (isDisposableEmail(email)) {
      return NextResponse.json(
        { error: "Disposable email addresses are not allowed." },
        { status: 400 }
      );
    }

    const db = await getDb();
    const collection = db.collection("partner_applications");

    // ── 6. Duplicate email ──
    const existing = await collection.findOne({
      email: email.toLowerCase().trim(),
    });
    if (existing) {
      return NextResponse.json(
        {
          error:
            "An application with this email already exists. Please check your inbox or contact support.",
        },
        { status: 409 }
      );
    }

    // ── 7. Rate limiting (5 per IP per 24 h) ──
    const ip = getClientIP(request);
    const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
    const recentCount = await collection.countDocuments({
      ip_address: ip,
      created_at: { $gte: oneDayAgo },
    });
    if (recentCount >= 5) {
      return NextResponse.json(
        {
          error:
            "Too many applications from this IP address. Please try again later.",
        },
        { status: 429 }
      );
    }

    // ── 8. Normalize URL & reachability ──
    let normalizedUrl = blog_url.trim();
    if (!/^https?:\/\//i.test(normalizedUrl)) {
      normalizedUrl = "https://" + normalizedUrl;
    }

    const domainReachable = await verifyDomainReachable(normalizedUrl);

    // ── 9. Sanitize inputs ──
    const safeName = sanitize(name);
    const safeEmail = email.toLowerCase().trim();
    const safeNotes = notes ? sanitize(notes) : "";
    const safeTraffic = traffic_estimate
      ? sanitize(traffic_estimate)
      : "Not specified";

    // ── 10. Insert application ──
    const applicationId = generateUUID();
    const now = new Date();

    const application = {
      application_id: applicationId,
      name: safeName,
      email: safeEmail,
      blog_url: normalizedUrl,
      traffic_estimate: safeTraffic,
      notes: safeNotes,
      consent: true,
      status: "pending" as const,
      domain_verification_status: domainReachable
        ? ("verified" as const)
        : ("failed" as const),
      domain_verified: domainReachable,
      created_at: now,
      ip_address: ip,
      messages: [],
    };

    await collection.insertOne(application);

    // ── 11. Send emails (non-blocking) ──
    const confirmHtml = applicationConfirmationEmail(safeName);
    sendEmail(safeEmail, "AdeasyNow — Application Received", confirmHtml).catch(
      (err) => console.error("Confirmation email error:", err)
    );

    const adminEmail = process.env.ADMIN_EMAIL || process.env.MAIL_FROM || "";
    if (adminEmail) {
      const adminHtml = applicationAdminNotificationEmail(
        safeName,
        safeEmail,
        normalizedUrl,
        safeTraffic
      );
      sendEmail(adminEmail, "New Partner Application", adminHtml).catch((err) =>
        console.error("Admin notification email error:", err)
      );
    }

    // ── 12. Success response ──
    return NextResponse.json(
      {
        success: true,
        message:
          "Application submitted successfully! Check your email for confirmation.",
        application_id: applicationId,
        domain_verification: {
          url: normalizedUrl,
          reachable: domainReachable,
          status: domainReachable ? "verified" : "failed",
        },
      },
      { status: 201 }
    );
  } catch (err) {
    console.error("Application submission error:", err);
    return NextResponse.json(
      { error: "Internal server error. Please try again later." },
      { status: 500 }
    );
  }
}
