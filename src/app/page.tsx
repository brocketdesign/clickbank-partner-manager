import Link from "next/link";
import {
  ArrowUpRight,
  DollarSign,
  BarChart3,
  Shield,
  Zap,
  Users,
  Star,
  CheckCircle,
  Send,
  Award,
  TrendingUp,
} from "lucide-react";
import FAQ from "@/components/FAQ";

const faqItems = [
  {
    question: "How long does the approval process take?",
    answer:
      "Most applications are reviewed within 24 hours. Once approved, you can start promoting offers and earning commissions immediately. We'll notify you by email as soon as your application status changes.",
  },
  {
    question: "What is the minimum payout threshold?",
    answer:
      "Our minimum payout is just $25 — one of the lowest in the industry. Payments are processed bi-weekly via ClickBank's built-in payment system, supporting direct deposit, wire transfer, and check.",
  },
  {
    question: "Do I need a website to apply?",
    answer:
      "Yes, you need a live website or blog where you plan to promote offers. We verify each site to ensure quality traffic and compliance. Social-media-only promoters may apply on a case-by-case basis.",
  },
  {
    question: "What kind of products will I be promoting?",
    answer:
      "You'll have access to a curated catalog of high-converting ClickBank offers across health, fitness, finance, and digital education niches. All products are vetted for quality and customer satisfaction.",
  },
];

const benefits = [
  {
    icon: ArrowUpRight,
    title: "High Commissions",
    description:
      "Earn up to 75% commission on every sale — among the highest rates in affiliate marketing.",
  },
  {
    icon: DollarSign,
    title: "$25 Min Payout",
    description:
      "Low payout threshold means you get paid faster. Most partners cash out within their first week.",
  },
  {
    icon: BarChart3,
    title: "Real-Time Analytics",
    description:
      "Track clicks, conversions, and earnings in real time with our intuitive partner dashboard.",
  },
  {
    icon: Shield,
    title: "Trusted Platform",
    description:
      "Built on ClickBank's 25+ year legacy. Your commissions are protected and paid on time, every time.",
  },
  {
    icon: Zap,
    title: "Instant Setup",
    description:
      "No technical skills required. Get your unique tracking links and start promoting in minutes.",
  },
  {
    icon: Users,
    title: "Dedicated Support",
    description:
      "Our partner success team is here to help you optimize campaigns and maximize your revenue.",
  },
];

const testimonials = [
  {
    name: "Sarah M.",
    role: "Health & Wellness Blogger",
    text: "I was skeptical at first, but AdeasyNow changed everything. I earned my first commission within 48 hours of getting approved. The tracking is flawless.",
    rating: 5,
  },
  {
    name: "James T.",
    role: "Finance Content Creator",
    text: "The low payout threshold was a game-changer for me. I was tired of waiting months to get paid elsewhere. Here I cash out every two weeks like clockwork.",
    rating: 5,
  },
  {
    name: "Linda K.",
    role: "Digital Marketing Educator",
    text: "The product quality is what keeps me here. My audience trusts my recommendations, and AdeasyNow's curated catalog makes picking winners easy.",
    rating: 5,
  },
];

export default function LandingPage() {
  return (
    <div className="min-h-screen bg-background">
      {/* ───── Sticky Header ───── */}
      <header className="glass sticky top-0 z-50 border-b border-border/60">
        <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
          <Link href="/" className="text-xl font-extrabold tracking-tight">
            <span className="gradient-text">AdeasyNow</span>
          </Link>
          <Link
            href="/apply"
            className="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90"
          >
            Apply Now
          </Link>
        </div>
      </header>

      {/* ───── Hero ───── */}
      <section className="relative overflow-hidden">
        {/* Background gradient */}
        <div
          aria-hidden
          className="pointer-events-none absolute inset-0 -z-10"
          style={{
            background:
              "radial-gradient(ellipse 80% 60% at 50% -10%, rgba(37,99,235,.12), transparent)",
          }}
        />
        <div className="mx-auto max-w-5xl px-6 pb-20 pt-28 text-center animate-in">
          <span className="mb-4 inline-block rounded-full bg-primary/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-primary">
            Partner Program
          </span>
          <h1 className="mx-auto max-w-3xl text-5xl font-extrabold leading-[1.1] tracking-tight text-foreground sm:text-6xl lg:text-7xl">
            Earn on{" "}
            <span className="gradient-text">Day&nbsp;One</span>
          </h1>
          <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-muted-foreground sm:text-xl">
            Join the AdeasyNow partner network and earn high commissions promoting
            top-converting ClickBank products — with real-time tracking and a $25
            minimum payout.
          </p>

          {/* KPI badges */}
          <div className="mx-auto mt-10 flex flex-wrap items-center justify-center gap-5 sm:gap-8">
            {[
              { label: "Conversion Rate", value: "30%+" },
              { label: "Avg Monthly", value: "$1,200" },
              { label: "Real-Time Tracking", value: "Live" },
            ].map((kpi) => (
              <div
                key={kpi.label}
                className="flex flex-col items-center rounded-2xl border border-border bg-card px-6 py-4 shadow-sm"
              >
                <span className="text-2xl font-extrabold text-primary">
                  {kpi.value}
                </span>
                <span className="mt-1 text-xs font-medium text-muted-foreground">
                  {kpi.label}
                </span>
              </div>
            ))}
          </div>

          <div className="mt-12">
            <Link
              href="/apply"
              className="inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-4 text-base font-bold text-primary-foreground shadow-lg shadow-primary/25 transition hover:shadow-primary/40 hover:brightness-110"
            >
              Apply Now
              <ArrowUpRight className="h-5 w-5" />
            </Link>
          </div>
        </div>
      </section>

      {/* ───── How It Works ───── */}
      <section className="bg-secondary/40 py-24">
        <div className="mx-auto max-w-5xl px-6 text-center">
          <h2 className="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">
            How It Works
          </h2>
          <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
            Three simple steps to start earning real commissions.
          </p>

          <div className="mt-14 grid gap-10 sm:grid-cols-3">
            {[
              {
                step: 1,
                icon: Send,
                title: "Apply",
                desc: "Fill out a quick application with your website details.",
              },
              {
                step: 2,
                icon: CheckCircle,
                title: "Get Approved",
                desc: "Our team reviews your site — most approvals within 24 hours.",
              },
              {
                step: 3,
                icon: TrendingUp,
                title: "Start Earning",
                desc: "Grab your tracking links and earn commissions from day one.",
              },
            ].map((s) => (
              <div key={s.step} className="animate-in flex flex-col items-center">
                <div className="relative mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10">
                  <s.icon className="h-7 w-7 text-primary" />
                  <span className="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground shadow">
                    {s.step}
                  </span>
                </div>
                <h3 className="mt-2 text-lg font-bold text-foreground">
                  {s.title}
                </h3>
                <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
                  {s.desc}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ───── Benefits ───── */}
      <section className="py-24">
        <div className="mx-auto max-w-6xl px-6 text-center">
          <h2 className="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">
            Why Partners Love Us
          </h2>
          <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
            Everything you need to grow your affiliate revenue, all in one place.
          </p>

          <div className="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {benefits.map((b) => (
              <div
                key={b.title}
                className="animate-in group rounded-2xl border border-border bg-card p-7 text-left shadow-sm transition hover:shadow-md hover:-translate-y-0.5"
              >
                <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-primary-foreground">
                  <b.icon className="h-6 w-6" />
                </div>
                <h3 className="text-base font-bold text-foreground">
                  {b.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                  {b.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ───── Testimonials ───── */}
      <section className="bg-secondary/40 py-24">
        <div className="mx-auto max-w-6xl px-6 text-center">
          <h2 className="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">
            Trusted by Partners
          </h2>
          <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
            Here&apos;s what our partners are saying.
          </p>

          <div className="mt-14 grid gap-6 sm:grid-cols-3">
            {testimonials.map((t) => (
              <div
                key={t.name}
                className="animate-in rounded-2xl border border-border bg-card p-7 text-left shadow-sm"
              >
                <div className="mb-3 flex gap-0.5">
                  {Array.from({ length: t.rating }).map((_, i) => (
                    <Star
                      key={i}
                      className="h-4 w-4 fill-warning text-warning"
                    />
                  ))}
                </div>
                <p className="text-sm leading-relaxed text-muted-foreground">
                  &ldquo;{t.text}&rdquo;
                </p>
                <div className="mt-5 flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                    {t.name[0]}
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-foreground">
                      {t.name}
                    </p>
                    <p className="text-xs text-muted-foreground">{t.role}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ───── FAQ ───── */}
      <section className="py-24">
        <div className="mx-auto max-w-3xl px-6">
          <h2 className="text-center text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">
            Frequently Asked Questions
          </h2>
          <p className="mx-auto mt-3 max-w-xl text-center text-muted-foreground">
            Quick answers to the questions we hear most.
          </p>
          <div className="mt-12">
            <FAQ items={faqItems} />
          </div>
        </div>
      </section>

      {/* ───── Final CTA ───── */}
      <section className="relative overflow-hidden py-24">
        <div
          aria-hidden
          className="pointer-events-none absolute inset-0 -z-10"
          style={{
            background:
              "radial-gradient(ellipse 70% 50% at 50% 100%, rgba(37,99,235,.10), transparent)",
          }}
        />
        <div className="mx-auto max-w-3xl px-6 text-center animate-in">
          <Award className="mx-auto mb-6 h-12 w-12 text-primary" />
          <h2 className="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">
            Ready to Start Earning?
          </h2>
          <p className="mx-auto mt-4 max-w-lg text-muted-foreground">
            Join thousands of creators already earning commissions with
            AdeasyNow. Applications are reviewed within 24 hours.
          </p>
          <div className="mt-10">
            <Link
              href="/apply"
              className="inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-4 text-base font-bold text-primary-foreground shadow-lg shadow-primary/25 transition hover:shadow-primary/40 hover:brightness-110"
            >
              Apply Now
              <ArrowUpRight className="h-5 w-5" />
            </Link>
          </div>
        </div>
      </section>

      {/* ───── Footer ───── */}
      <footer className="border-t border-border py-10">
        <div className="mx-auto max-w-6xl px-6 text-center text-sm text-muted-foreground">
          &copy; {new Date().getFullYear()} AdeasyNow. All rights reserved.
        </div>
      </footer>
    </div>
  );
}
