import nodemailer from "nodemailer";

const transporter = process.env.SMTP_HOST && process.env.SMTP_USER
  ? nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: Number(process.env.SMTP_PORT) || 587,
      secure: false,
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS,
      },
    })
  : null;

export async function sendEmail(
  to: string,
  subject: string,
  html: string
): Promise<boolean> {
  const from = process.env.MAIL_FROM || "contact@adeasynow.com";

  if (transporter) {
    try {
      await transporter.sendMail({ from, to, subject, html });
      return true;
    } catch (err) {
      console.error("SMTP error:", err);
      return false;
    }
  }

  // Log email in development if no SMTP configured
  console.log(`[Email] To: ${to}, Subject: ${subject}`);
  return true;
}

export function applicationConfirmationEmail(name: string): string {
  return `
    <div style="font-family:Inter,system-ui,sans-serif;max-width:600px;margin:0 auto;padding:40px 20px">
      <div style="text-align:center;margin-bottom:32px">
        <h1 style="color:#2563eb;font-size:24px;margin-bottom:8px">AdeasyNow</h1>
      </div>
      <h2 style="color:#0f172a;font-size:20px;">Hi ${name},</h2>
      <p style="color:#475569;line-height:1.6;">Thank you for applying to AdeasyNow! We've received your application and our team will review it within 3-5 business days.</p>
      <p style="color:#475569;line-height:1.6;">You'll receive an email once your application has been reviewed.</p>
      <div style="margin-top:32px;padding-top:16px;border-top:1px solid #e2e8f0;">
        <p style="color:#94a3b8;font-size:13px;">— The AdeasyNow Team</p>
      </div>
    </div>
  `;
}

export function applicationAdminNotificationEmail(
  name: string,
  email: string,
  blogUrl: string,
  traffic: string
): string {
  return `
    <div style="font-family:Inter,system-ui,sans-serif;max-width:600px;margin:0 auto;padding:40px 20px">
      <h2 style="color:#0f172a;font-size:20px;">New Partner Application</h2>
      <table style="width:100%;border-collapse:collapse;margin-top:16px;">
        <tr><td style="padding:8px;color:#64748b;border-bottom:1px solid #e2e8f0;">Name</td><td style="padding:8px;border-bottom:1px solid #e2e8f0;">${name}</td></tr>
        <tr><td style="padding:8px;color:#64748b;border-bottom:1px solid #e2e8f0;">Email</td><td style="padding:8px;border-bottom:1px solid #e2e8f0;">${email}</td></tr>
        <tr><td style="padding:8px;color:#64748b;border-bottom:1px solid #e2e8f0;">Blog URL</td><td style="padding:8px;border-bottom:1px solid #e2e8f0;">${blogUrl}</td></tr>
        <tr><td style="padding:8px;color:#64748b;">Traffic</td><td style="padding:8px;">${traffic} visitors/month</td></tr>
      </table>
      <p style="margin-top:24px;"><a href="${process.env.NEXTAUTH_URL || ""}/admin/applications" style="background:#2563eb;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;">Review Application</a></p>
    </div>
  `;
}
