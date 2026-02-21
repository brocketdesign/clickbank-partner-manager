"use client";

import { useState, useCallback, useEffect } from "react";
import Link from "next/link";
import {
  User,
  Mail,
  Globe,
  CheckCircle2,
  Loader2,
  ArrowLeft,
} from "lucide-react";

function generateCaptcha() {
  const a = Math.floor(Math.random() * 20) + 1;
  const b = Math.floor(Math.random() * 20) + 1;
  return { a, b, answer: a + b };
}

export default function ApplyPage() {
  const [captcha, setCaptcha] = useState(generateCaptcha);
  const [submitted, setSubmitted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const [form, setForm] = useState({
    name: "",
    email: "",
    blog_url: "",
    traffic_estimate: "",
    notes: "",
    consent: false,
    captcha_answer: "",
  });

  // Regenerate captcha on mount (avoids hydration mismatch by being stable per-session)
  useEffect(() => {
    setCaptcha(generateCaptcha());
  }, []);

  const onChange = useCallback(
    (
      e: React.ChangeEvent<
        HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
      >
    ) => {
      const { name, value, type } = e.target;
      setForm((prev) => ({
        ...prev,
        [name]:
          type === "checkbox" ? (e.target as HTMLInputElement).checked : value,
      }));
    },
    []
  );

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const res = await fetch("/api/partners/apply", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: form.name,
          email: form.email,
          blog_url: form.blog_url,
          traffic_estimate: form.traffic_estimate,
          notes: form.notes,
          consent: form.consent,
          captcha_a: captcha.a,
          captcha_b: captcha.b,
          captcha_result: Number(form.captcha_answer),
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.error || "Something went wrong. Please try again.");
        setCaptcha(generateCaptcha());
        setForm((prev) => ({ ...prev, captcha_answer: "" }));
      } else {
        setSubmitted(true);
      }
    } catch {
      setError("Network error. Please check your connection and try again.");
    } finally {
      setLoading(false);
    }
  };

  /* ───── Success screen ───── */
  if (submitted) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background px-4">
        <div
          aria-hidden
          className="pointer-events-none fixed inset-0 -z-10"
          style={{
            background:
              "radial-gradient(ellipse 80% 60% at 50% 0%, rgba(37,99,235,.08), transparent)",
          }}
        />
        <div className="animate-in w-full max-w-md rounded-2xl border border-border bg-card p-10 text-center shadow-xl">
          <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-success/10">
            <CheckCircle2 className="h-9 w-9 text-success" />
          </div>
          <h1 className="text-2xl font-extrabold text-foreground">
            Application Submitted!
          </h1>
          <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
            Thank you for applying to AdeasyNow. We&apos;ll review your
            application within 24 hours and send you a confirmation by email.
          </p>
          <Link
            href="/"
            className="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to Home
          </Link>
        </div>
      </div>
    );
  }

  /* ───── Form ───── */
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-16">
      {/* Background */}
      <div
        aria-hidden
        className="pointer-events-none fixed inset-0 -z-10"
        style={{
          background:
            "radial-gradient(ellipse 80% 60% at 50% -10%, rgba(37,99,235,.10), transparent)",
        }}
      />

      <div className="animate-in w-full max-w-lg">
        {/* Back */}
        <Link
          href="/"
          className="mb-6 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition"
        >
          <ArrowLeft className="h-4 w-4" />
          Home
        </Link>

        <div className="rounded-2xl border border-border bg-card p-8 shadow-xl sm:p-10">
          {/* Heading */}
          <h1 className="text-2xl font-extrabold tracking-tight text-foreground">
            Apply to{" "}
            <span className="gradient-text">AdeasyNow</span>
          </h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Earn on Day One — $25 minimum payout. Reviewed within 24 hours.
          </p>

          <form onSubmit={handleSubmit} className="mt-8 space-y-5">
            {/* Name */}
            <FloatingField
              id="name"
              label="Full Name"
              icon={<User className="h-4 w-4" />}
              required
            >
              <input
                id="name"
                name="name"
                type="text"
                required
                value={form.name}
                onChange={onChange}
                placeholder=" "
                className="peer w-full rounded-lg border border-border bg-background py-3 pl-10 pr-4 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
              />
            </FloatingField>

            {/* Email */}
            <FloatingField
              id="email"
              label="Email Address"
              icon={<Mail className="h-4 w-4" />}
              required
            >
              <input
                id="email"
                name="email"
                type="email"
                required
                value={form.email}
                onChange={onChange}
                placeholder=" "
                className="peer w-full rounded-lg border border-border bg-background py-3 pl-10 pr-4 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
              />
            </FloatingField>

            {/* Blog URL */}
            <FloatingField
              id="blog_url"
              label="Blog / Website URL"
              icon={<Globe className="h-4 w-4" />}
              required
            >
              <input
                id="blog_url"
                name="blog_url"
                type="url"
                required
                value={form.blog_url}
                onChange={onChange}
                placeholder=" "
                className="peer w-full rounded-lg border border-border bg-background py-3 pl-10 pr-4 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
              />
            </FloatingField>

            {/* Traffic */}
            <div>
              <label
                htmlFor="traffic_estimate"
                className="mb-1.5 block text-sm font-medium text-foreground"
              >
                Monthly Traffic
              </label>
              <select
                id="traffic_estimate"
                name="traffic_estimate"
                value={form.traffic_estimate}
                onChange={onChange}
                required
                className="w-full rounded-lg border border-border bg-background px-4 py-3 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
              >
                <option value="" disabled>
                  Select estimated traffic
                </option>
                <option value="1K-5K">1K – 5K visitors/month</option>
                <option value="5K-10K">5K – 10K visitors/month</option>
                <option value="10K-25K">10K – 25K visitors/month</option>
                <option value="25K-50K">25K – 50K visitors/month</option>
                <option value="50K+">50K+ visitors/month</option>
              </select>
            </div>

            {/* Notes */}
            <div>
              <label
                htmlFor="notes"
                className="mb-1.5 block text-sm font-medium text-foreground"
              >
                Notes{" "}
                <span className="font-normal text-muted-foreground">
                  (optional)
                </span>
              </label>
              <textarea
                id="notes"
                name="notes"
                rows={3}
                value={form.notes}
                onChange={onChange}
                placeholder="Tell us about your audience, niche, or promotion plans…"
                className="w-full rounded-lg border border-border bg-background px-4 py-3 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
              />
            </div>

            {/* CAPTCHA */}
            <div>
              <label
                htmlFor="captcha_answer"
                className="mb-1.5 block text-sm font-medium text-foreground"
              >
                What is{" "}
                <span className="font-mono font-bold text-primary">
                  {captcha.a} + {captcha.b}
                </span>
                ?
              </label>
              <input
                id="captcha_answer"
                name="captcha_answer"
                type="number"
                required
                value={form.captcha_answer}
                onChange={onChange}
                placeholder="Your answer"
                className="w-full rounded-lg border border-border bg-background px-4 py-3 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
              />
            </div>

            {/* Consent */}
            <label className="flex items-start gap-3 cursor-pointer">
              <input
                type="checkbox"
                name="consent"
                checked={form.consent}
                onChange={onChange}
                required
                className="mt-0.5 h-4 w-4 rounded border-border text-primary accent-primary"
              />
              <span className="text-xs leading-relaxed text-muted-foreground">
                I agree to the terms of service and confirm the information
                provided is accurate. I consent to being contacted regarding my
                application.
              </span>
            </label>

            {/* Error */}
            {error && (
              <div className="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                {error}
              </div>
            )}

            {/* Submit */}
            <button
              type="submit"
              disabled={loading}
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 text-sm font-bold text-primary-foreground shadow-lg shadow-primary/20 transition hover:brightness-110 disabled:opacity-60"
            >
              {loading ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Submitting…
                </>
              ) : (
                "Submit Application"
              )}
            </button>
          </form>
        </div>

        <p className="mt-6 text-center text-xs text-muted-foreground">
          Already a partner?{" "}
          <Link href="/admin/login" className="text-primary hover:underline">
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
}

/* ───── Floating-label wrapper ───── */
function FloatingField({
  id,
  label,
  icon,
  required,
  children,
}: {
  id: string;
  label: string;
  icon: React.ReactNode;
  required?: boolean;
  children: React.ReactNode;
}) {
  return (
    <div className="relative">
      {children}
      {/* Icon */}
      <div className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground peer-focus:text-primary transition">
        {icon}
      </div>
      {/* Floating label */}
      <label
        htmlFor={id}
        className="pointer-events-none absolute left-10 top-1/2 -translate-y-1/2 text-sm text-muted-foreground transition-all
          peer-focus:-translate-y-[1.85rem] peer-focus:left-3 peer-focus:text-xs peer-focus:font-semibold peer-focus:text-primary
          peer-[:not(:placeholder-shown)]:-translate-y-[1.85rem] peer-[:not(:placeholder-shown)]:left-3 peer-[:not(:placeholder-shown)]:text-xs peer-[:not(:placeholder-shown)]:font-semibold"
      >
        {label}
        {required && <span className="text-destructive"> *</span>}
      </label>
    </div>
  );
}
