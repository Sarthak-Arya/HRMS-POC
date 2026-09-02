import { createFileRoute } from "@tanstack/react-router";
import { AiMock } from "@/components/landing/AiMock";
import { RunMock } from "@/components/landing/RunMock";
import {
  Eyebrow,
  Icon,
  Logo,
  Pill,
  PrimaryButton,
  SecondaryButton,
  SectionHeading,
  StatusBadge,
} from "@/components/landing/primitives";

const title = "FlipCore: Enterprise Payroll & HRMS for Indian Companies";
const description =
  "Run payroll with confidence: attendance lock, salary structures, PF/ESI, loan EMIs and adjustments in one locked monthly run with payslips and salary sheets.";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title },
      { name: "description", content: description },
      { property: "og:title", content: title },
      { property: "og:description", content: description },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary_large_image" },
    ],
  }),
  component: Landing,
});

const navLinks = [
  { label: "Product", href: "#product" },
  { label: "Payroll", href: "#payroll" },
  { label: "Features", href: "#features" },
  { label: "Security", href: "#security" },
  { label: "AI", href: "#ai" },
];

const stats = [
  {
    icon: "event_repeat",
    figure: "1 run",
    label: "per company, per month",
    body: "No parallel sheets, no private copies. 150 employees in this demo dataset, 8 work locations.",
  },
  {
    icon: "history",
    figure: "3 days",
    label: "attendance lock to locked run",
    body: "Jun 2026 closed on Jul 1, generated Jul 2, locked Jul 3. Corrections land in the next cycle.",
  },
  {
    icon: "admin_panel_settings",
    figure: "21 permissions",
    label: "scoped per company",
    body: "payroll.manage, payroll.approve, reports.view and 18 more, assigned per role and company.",
  },
];

const problems = [
  {
    icon: "table_view",
    pain: "Spreadsheets drift after lock day",
    solve:
      "Payroll lives in a run, not a file. Once finance marks the run LOCKED, the numbers stop moving. Later fixes become adjustments in the next run.",
  },
  {
    icon: "event_busy",
    pain: "Attendance isn't closed before salary is generated",
    solve:
      "The monthly attendance grid must be locked before a run can leave DRAFT. Missing days are listed as gaps, per department and location.",
  },
  {
    icon: "rule_settings",
    pain: "PF and ESI applied inconsistently",
    solve:
      "Components like Basic, HRA and PF are defined once, then assigned through salary structures. Every payslip in the run uses the same definitions.",
  },
];

const modules = [
  {
    icon: "groups",
    label: "Employees",
    title: "One employee master",
    body: "Add or import employees with PF and ESI flags, departments, and work locations. Every payroll line traces back to this record.",
    snippet: "150 active · 8 locations",
  },
  {
    icon: "calendar_month",
    label: "Attendance",
    title: "Close the month first",
    body: "Monthly grid with policies, leave and comp-off. Lock the month so payroll calculates on final days, not moving numbers.",
    snippet: "JUNE 2026 · LOCKED",
  },
  {
    icon: "account_balance_wallet",
    label: "Compensation",
    title: "Structures, not formulas per person",
    body: "Reusable components (Basic, HRA, PF) combined into salary structures. Assign a structure per employee and revise with effect dates.",
    snippet: "12 structures · 21 components",
  },
  {
    icon: "playlist_add_check",
    label: "Payroll runs",
    title: "Generate, review, approve, pay",
    body: "Generate the batch for the whole company, then review earnings, deductions and employer contributions per employee. Approve, then mark paid.",
    snippet: "RUN-2026-06 · COMPLETED",
  },
  {
    icon: "request_quote",
    label: "Adjustments & loans",
    title: "Arrears, bonuses, recoveries, EMIs",
    body: "Attach one-off adjustments to a specific run. Loan EMIs schedule installments and deduct automatically until the balance clears.",
    snippet: "3 adjustments · 11 EMIs",
  },
  {
    icon: "description",
    label: "Reports",
    title: "Salary sheets and payslip PDFs",
    body: "Export the salary sheet for the run, or generate payslips one by one and in bulk. PF, ESI and GST details print on the payslip.",
    snippet: "150 payslips · 1 salary sheet",
  },
];

const lifecycle = [
  {
    status: "draft" as const,
    title: "Build the month",
    body: "Create the run for the company and period. Confirm headcount, structures, and that attendance is locked.",
  },
  {
    status: "processing" as const,
    title: "Calculate",
    body: "Earnings, deductions and employer contributions are computed from attendance and assigned compensation.",
  },
  {
    status: "completed" as const,
    title: "Finance reviews",
    body: "Controllers check nets, adjustments and loan EMIs. Audit logs show who changed what, and when.",
  },
  {
    status: "locked" as const,
    title: "Immutable",
    body: "The run is frozen with a version snapshot. Later corrections are new adjustments in a future run.",
  },
];

const security = [
  {
    icon: "domain",
    label: "Company scope",
    title: "Data is scoped per company",
    body: "Every employee, run and report belongs to a company. Multi-company groups keep payroll separate by design.",
  },
  {
    icon: "key",
    label: "RBAC",
    title: "Role-based access",
    body: "Granular permissions such as payroll.manage, payroll.approve and reports.view decide who can generate, approve, or only read.",
  },
  {
    icon: "receipt_long",
    label: "Audit log",
    title: "Centralized audit trail",
    body: "Structure changes, adjustments, approvals and lock events are recorded with actor, timestamp and previous value.",
  },
  {
    icon: "lock_clock",
    label: "Snapshots",
    title: "Immutable payroll history",
    body: "Locked runs store a snapshot of the payslip lines used, so a reprint months later matches what finance approved.",
  },
  {
    icon: "monitor_heart",
    label: "Observability",
    title: "Failed batches surface early",
    body: "Batch generation is instrumented. Failures are logged with the affected employees instead of failing silently mid-run.",
  },
];

function Landing() {
  return (
    <div id="top" className="min-h-screen bg-background">
      {/* NAV */}
      <header className="sticky top-0 z-50 border-b border-border bg-background/90 backdrop-blur">
        <nav className="section-shell flex h-[72px] items-center justify-between gap-6">
          <Logo />
          <ul className="hidden items-center gap-7 lg:flex">
            {navLinks.map((l) => (
              <li key={l.label}>
                <a
                  href={l.href}
                  className="text-[14px] text-muted-foreground transition-colors hover:text-primary"
                >
                  {l.label}
                </a>
              </li>
            ))}
          </ul>
          <div className="flex items-center gap-2">
            <SecondaryButton className="hidden px-4 py-2.5 sm:inline-flex">Sign in</SecondaryButton>
            <PrimaryButton className="px-4 py-2.5">Request a demo</PrimaryButton>
          </div>
        </nav>
      </header>

      <main>
        {/* HERO */}
        <section className="section-shell grid items-center gap-12 py-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:py-24">
          <div>
            <Eyebrow>Enterprise Payroll &amp; HRMS</Eyebrow>
            <h1 className="mt-4 text-4xl leading-[1.08] text-foreground sm:text-5xl lg:text-[3.35rem]">
              Close payroll for 150 employees in{" "}
              <span className="relative inline-block">
                3 working days
                <svg
                  aria-hidden="true"
                  viewBox="0 0 220 14"
                  preserveAspectRatio="none"
                  className="absolute -bottom-1.5 left-0 h-3 w-full text-primary"
                >
                  <path
                    d="M2 9.5C38 4.2 74 3.4 110 6.1c36 2.7 72 3.1 108 -3.3"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="3"
                    strokeLinecap="round"
                  />
                </svg>
              </span>
              , not on the last night of the month.
            </h1>
            <p className="mt-6 max-w-xl text-[15px] leading-relaxed text-muted-foreground">
              Lock attendance on day 1. Generate the run on day 2. Finance locks it on day 3. PF, ESI, loan
              EMIs and arrears all live inside that one run, so nobody is comparing four spreadsheets at 11pm.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <PrimaryButton>Request a demo</PrimaryButton>
              <SecondaryButton href="#payroll">See how a payroll run works</SecondaryButton>
            </div>
            <p className="mt-3 text-[12.5px] text-muted-foreground">
              25 minutes, on your own salary structures. No card, and you don't have to move any data to look.
            </p>

            <div className="mt-8 flex flex-wrap gap-2">
              <Pill>
                <Icon name="verified_user" className="text-[15px] text-primary" />
                Built for Indian statutory payroll
              </Pill>
              <Pill>PF</Pill>
              <Pill>ESI</Pill>
              <Pill>GST on payslips</Pill>
            </div>
          </div>
          <RunMock />
        </section>

        {/* TRUST STRIP */}
        <section className="border-y border-border bg-card/70">
          <div className="section-shell py-14">
            <p className="mono-label text-[10px] text-faint">
              For payroll operators, HR admins, and finance reviewers.
            </p>
            <div className="mt-6 grid gap-4 md:grid-cols-3">
              {stats.map((s, i) => (
                <div
                  key={s.label}
                  className="ui-card"
                  style={{ transform: `rotate(${[-0.5, 0.35, -0.2][i] ?? 0}deg)` }}
                >
                  <Icon name={s.icon} className="text-[22px] text-primary" />
                  <p className="mt-4 font-[family-name:var(--font-display)] text-[24px] leading-none font-bold text-foreground tabular">
                    {s.figure}
                  </p>
                  <p className="mono-label mt-2 text-[10px] text-faint">{s.label}</p>
                  <p className="mt-3 text-[13px] leading-relaxed text-muted-foreground">{s.body}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* PROBLEM → SOLUTION */}
        <section id="product" className="section-shell py-20 lg:py-24">
          <SectionHeading
            eyebrow="Why payroll slips"
            title="The month doesn't break at calculation. It breaks at control."
            body="Ask any payroll operator what went wrong last month and you'll usually hear one of these three. They aren't maths problems, they're control problems."
          />

          <div className="mt-10 grid gap-4 lg:grid-cols-3">
            {problems.map((p) => (
              <div key={p.pain} className="ui-card flex flex-col">
                <span className="flex size-11 items-center justify-center rounded-xl bg-muted">
                  <Icon name={p.icon} className="text-[21px] text-destructive" />
                </span>
                <h3 className="mt-5 text-[18px] leading-snug text-foreground">{p.pain}</h3>
                <div className="mt-4 border-t border-border pt-4">
                  <p className="mono-label text-[9px] text-primary">How FlipCore handles it</p>
                  <p className="mt-2 text-[13px] leading-relaxed text-muted-foreground">{p.solve}</p>
                </div>
              </div>
            ))}
          </div>
          <div className="mt-6 flex flex-wrap items-center gap-3 rounded-[20px] border border-border bg-accent px-6 py-5">
            <Icon name="lock" className="text-[20px] text-primary" />
            <p className="text-[14px] text-foreground">
              A single payroll run per company and month, moving through
            </p>
            <div className="flex flex-wrap gap-1.5">
              <StatusBadge status="draft" />
              <StatusBadge status="processing" />
              <StatusBadge status="completed" />
              <StatusBadge status="locked" />
            </div>
            <p className="text-[14px] text-foreground">Locked runs cannot be silently edited.</p>
          </div>
        </section>

        {/* MODULES */}
        <section id="features" className="border-y border-border bg-card/70">
          <div className="section-shell py-20 lg:py-24">
            <SectionHeading
              eyebrow="Product modules"
              title="Everything a payroll operator touches in a month."
              body="Six modules, one data model. Employees feed attendance, attendance feeds the run, the run produces payslips and salary sheets."
            />
            <div className="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              {modules.map((m) => (
                <article key={m.label} className="ui-card flex flex-col">
                  <span className="flex size-11 items-center justify-center rounded-xl bg-secondary">
                    <Icon name={m.icon} className="text-[21px] text-primary" />
                  </span>
                  <p className="mono-label mt-5 text-[9px] text-faint">{m.label}</p>
                  <h3 className="mt-2 text-[19px] leading-snug text-foreground">{m.title}</h3>
                  <p className="mt-3 flex-1 text-[13px] leading-relaxed text-muted-foreground">{m.body}</p>
                  <p className="tabular mt-5 rounded-xl border border-border bg-muted px-3 py-2 text-[11px] text-muted-foreground">
                    {m.snippet}
                  </p>
                </article>
              ))}
            </div>
          </div>
        </section>

        {/* LIFECYCLE */}
        <section id="payroll" className="section-shell py-20 lg:py-24">
          <SectionHeading
            eyebrow="How a run works"
            title="Four states. One direction. No silent edits."
            body="Every transition is written to the audit log with the actor and timestamp, and completed runs keep a version snapshot so finance can see who changed what."
          />
          <ol className="mt-10 grid gap-4 lg:grid-cols-4">
            {lifecycle.map((s, i) => (
              <li key={s.status} className="ui-card relative flex flex-col">
                <div className="flex items-center justify-between">
                  <StatusBadge status={s.status} />
                  <span className="tabular text-[12px] text-faint">0{i + 1}</span>
                </div>
                <h3 className="mt-4 text-[18px] text-foreground">{s.title}</h3>
                <p className="mt-2 text-[13px] leading-relaxed text-muted-foreground">{s.body}</p>
              </li>
            ))}
          </ol>
          <div className="mt-6 flex flex-wrap items-center gap-4 rounded-[20px] border border-border bg-card px-6 py-5 shadow-soft">
            <p className="mono-label text-[10px] text-faint">Employee payslip status</p>
            <div className="flex flex-wrap items-center gap-2">
              <StatusBadge status="draft" />
              <Icon name="arrow_forward" className="text-[16px] text-faint" />
              <StatusBadge status="approved" icon="check" />
              <Icon name="arrow_forward" className="text-[16px] text-faint" />
              <StatusBadge status="paid" icon="payments" />
            </div>
            <p className="text-[13px] text-muted-foreground">
              Status is never colour-only. Every badge carries its label.
            </p>
          </div>
        </section>

        {/* AI */}
        <section id="ai" className="border-y border-border bg-card/70">
          <div className="section-shell grid items-center gap-12 py-20 lg:grid-cols-2 lg:py-24">
            <div>
              <Eyebrow>Payroll AI</Eyebrow>
              <h2 className="mt-3 text-3xl leading-tight text-foreground sm:text-4xl">
                Ask Payroll AI. Verify before you approve.
              </h2>
              <p className="mt-5 text-[15px] leading-relaxed text-muted-foreground">
                Operators can ask about readiness, missing attendance, and run status without digging through six
                screens. Answers point back to the employees and runs they came from.
              </p>
              <ul className="mt-6 space-y-3">
                {[
                  "Readiness checks before a run leaves DRAFT",
                  "Attendance gaps by department and location",
                  "Run status, adjustments, and loan EMI balances",
                ].map((t) => (
                  <li key={t} className="flex items-start gap-3 text-[14px] text-muted-foreground">
                    <Icon name="check_circle" className="mt-0.5 text-[18px] text-success" />
                    {t}
                  </li>
                ))}
              </ul>
              <p className="mt-6 rounded-[16px] border border-warning-foreground/25 bg-warning px-4 py-3 text-[13px] text-warning-foreground">
                Payroll AI can make mistakes. Verify important financial data before final approval.
              </p>
              <p className="mt-3 text-[12px] text-faint">
                Payroll AI does not file statutory returns and does not pay salaries. Operators approve and pay.
              </p>
            </div>
            <AiMock />
          </div>
        </section>

        {/* SECURITY */}
        <section id="security" className="section-shell py-20 lg:py-24">
          <SectionHeading
            eyebrow="Security &amp; trust"
            title="Controls a finance reviewer can check, not just read about."
            body="FlipCore is built for auditability first: scoped data, explicit permissions, and history that cannot be rewritten after lock."
          />
          <div className="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {security.map((s) => (
              <div key={s.label} className="ui-card">
                <div className="flex items-center gap-3">
                  <span className="flex size-10 items-center justify-center rounded-xl bg-muted">
                    <Icon name={s.icon} className="text-[20px] text-primary" />
                  </span>
                  <p className="mono-label text-[9px] text-faint">{s.label}</p>
                </div>
                <h3 className="mt-4 text-[17px] leading-snug text-foreground">{s.title}</h3>
                <p className="mt-2 text-[13px] leading-relaxed text-muted-foreground">{s.body}</p>
              </div>
            ))}
          </div>
        </section>

        {/* WHAT A MONTH LOOKS LIKE */}
        <section className="border-y border-border bg-card/70">
          <div className="section-shell py-20">
            <div className="grid gap-10 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-center">
              <div>
                <Eyebrow>Month close</Eyebrow>
                <h2 className="mt-3 text-3xl leading-tight text-foreground sm:text-[2.4rem]">
                  A June close, day by day.
                </h2>
                <p className="mt-4 max-w-md text-[14px] leading-relaxed text-muted-foreground">
                  This is the demo dataset in this build, not a customer story. We'd rather show you the boring
                  version than a chart nobody can check.
                </p>
                <p
                  className="mt-6 max-w-sm rounded-[18px] border border-dashed border-primary/40 bg-card p-5 text-[13.5px] leading-relaxed text-muted-foreground"
                  style={{ transform: "rotate(-0.6deg)" }}
                >
                  <span className="mono-label block text-[9px] text-primary">Note from the build</span>
                  <span className="mt-2 block">
                    The two late arrear cases on Jul 09 are real behaviour, not a bug. Once a run is locked we
                    push corrections into the next month instead of quietly editing history.
                  </span>
                </p>
              </div>

              <ol className="grid gap-3 sm:grid-cols-2">
                {[
                  { day: "Jul 01", label: "Attendance locked", body: "Both plants closed. 6 gap days resolved." },
                  { day: "Jul 02", label: "Run generated", body: "150 employees, 12 structures applied." },
                  { day: "Jul 03", label: "Run locked", body: "Approved by finance, 150 payslips issued." },
                  { day: "Jul 09", label: "Arrears queued", body: "2 late cases moved into the July run." },
                ].map((s) => (
                  <li key={s.day} className="ui-card">
                    <p className="mono-label text-[9px] text-faint tabular">{s.day}</p>
                    <p className="mt-3 text-[15px] font-medium text-foreground">{s.label}</p>
                    <p className="mt-1 text-[13px] leading-relaxed text-muted-foreground">{s.body}</p>
                  </li>
                ))}
              </ol>
            </div>
          </div>
        </section>

        {/* WHO IT IS NOT FOR */}
        <section className="section-shell py-20 lg:py-24">
          <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div>
              <Eyebrow>Fit check</Eyebrow>
              <h2 className="mt-3 text-3xl leading-tight text-foreground sm:text-[2.3rem]">
                Where FlipCore stops.
              </h2>
              <p className="mt-4 max-w-md text-[14px] leading-relaxed text-muted-foreground">
                Better you hear it now than three months into a rollout.
              </p>
            </div>
            <ul className="grid gap-3">
              {[
                {
                  no: "Not a filing service",
                  body: "It calculates PF and ESI and prints them on payslips. It does not submit ECR or challans for you.",
                },
                {
                  no: "Not for 5-person teams",
                  body: "The run lifecycle, RBAC and approvals only pay off from roughly 50 employees upward.",
                },
                {
                  no: "No payment rails yet",
                  body: "You export the salary sheet and pay through your bank. There is no in-product bank transfer.",
                },
                {
                  no: "No non-India statutory setup",
                  body: "Components, PF, ESI and payslip formats assume Indian payroll rules only.",
                },
              ].map((n) => (
                <li key={n.no} className="flex gap-3 rounded-[18px] border border-border bg-card p-5">
                  <Icon name="do_not_disturb_on" className="mt-0.5 text-[19px] text-destructive" />
                  <div>
                    <p className="text-[15px] font-medium text-foreground">{n.no}</p>
                    <p className="mt-1 text-[13px] leading-relaxed text-muted-foreground">{n.body}</p>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </section>

        {/* FINAL CTA */}
        <section id="demo" className="section-shell pb-20 lg:pb-24">
          <div className="rounded-[24px] border border-border bg-accent px-6 py-14 text-center shadow-soft sm:px-12">
            <h2 className="mx-auto max-w-2xl text-3xl leading-tight text-foreground sm:text-[2.6rem]">
              Close the month without rewriting last month.
            </h2>
            <div className="mt-8 flex flex-wrap justify-center gap-3">
              <PrimaryButton>Request a demo</PrimaryButton>
              <SecondaryButton className="bg-card">Sign in</SecondaryButton>
            </div>
            <p className="mt-4 text-[12.5px] text-muted-foreground">
              Takes about 45 seconds to request. We reply with 3 slots, not a sales sequence.
            </p>
            <p className="mono-label mt-6 text-[10px] text-faint">
              Multi-company ready · Indian PF, ESI, and GST details on payslips
            </p>
          </div>
        </section>
      </main>

      {/* FOOTER */}
      <footer className="border-t border-border bg-card">
        <div className="section-shell flex flex-col gap-8 py-12">
          <div className="flex flex-wrap items-center justify-between gap-6">
            <Logo />
            <ul className="flex flex-wrap items-center gap-6">
              {[
                { label: "Product", href: "#product" },
                { label: "Security", href: "#security" },
                { label: "Docs", href: "#product" },
                { label: "Sign in", href: "#signin" },
              ].map((l) => (
                <li key={l.label}>
                  <a
                    href={l.href}
                    className="mono-label text-[10px] text-muted-foreground transition-colors hover:text-primary"
                  >
                    {l.label}
                  </a>
                </li>
              ))}
            </ul>
          </div>
          <p className="border-t border-border pt-6 text-[12px] leading-relaxed text-faint">
            FlipCore · Enterprise Admin · proof-of-concept demo product. Screens and figures shown here are
            sample data. No certifications or client claims are implied.
          </p>
        </div>
      </footer>
    </div>
  );
}
