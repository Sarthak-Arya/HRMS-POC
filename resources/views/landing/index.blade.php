<x-layouts.landing>
    @php
        $navLinks = [
            ['label' => 'Product', 'href' => '#product'],
            ['label' => 'Payroll', 'href' => '#payroll'],
            ['label' => 'Features', 'href' => '#features'],
            ['label' => 'Security', 'href' => '#security'],
            ['label' => 'AI', 'href' => '#ai'],
        ];
        $stats = [
            [
                'icon' => 'event_repeat',
                'figure' => '1 run',
                'label' => 'per company, per month',
                'body' => 'No parallel sheets, no private copies. 150 employees in this demo dataset, 8 work locations.',
                'rotate' => '-0.5deg',
            ],
            [
                'icon' => 'history',
                'figure' => '3 days',
                'label' => 'attendance lock to locked run',
                'body' => 'Jun 2026 closed on Jul 1, generated Jul 2, locked Jul 3. Corrections land in the next cycle.',
                'rotate' => '0.35deg',
            ],
            [
                'icon' => 'admin_panel_settings',
                'figure' => '21 permissions',
                'label' => 'scoped per company',
                'body' => 'payroll.manage, payroll.approve, reports.view and 18 more, assigned per role and company.',
                'rotate' => '-0.2deg',
            ],
        ];
        $problems = [
            [
                'icon' => 'table_view',
                'pain' => 'Spreadsheets drift after lock day',
                'solve' => 'Payroll lives in a run, not a file. Once finance marks the run LOCKED, the numbers stop moving. Later fixes become adjustments in the next run.',
            ],
            [
                'icon' => 'event_busy',
                'pain' => "Attendance isn't closed before salary is generated",
                'solve' => 'The monthly attendance grid must be locked before a run can leave DRAFT. Missing days are listed as gaps, per department and location.',
            ],
            [
                'icon' => 'rule_settings',
                'pain' => 'PF and ESI applied inconsistently',
                'solve' => 'Components like Basic, HRA and PF are defined once, then assigned through salary structures. Every payslip in the run uses the same definitions.',
            ],
        ];
        $modules = [
            ['icon' => 'groups', 'label' => 'Employees', 'title' => 'One employee master', 'body' => 'Add or import employees with PF and ESI flags, departments, and work locations. Every payroll line traces back to this record.', 'snippet' => '150 active · 8 locations'],
            ['icon' => 'calendar_month', 'label' => 'Attendance', 'title' => 'Close the month first', 'body' => 'Monthly grid with policies, leave and comp-off. Lock the month so payroll calculates on final days, not moving numbers.', 'snippet' => 'JUNE 2026 · LOCKED'],
            ['icon' => 'account_balance_wallet', 'label' => 'Compensation', 'title' => 'Structures, not formulas per person', 'body' => 'Reusable components (Basic, HRA, PF) combined into salary structures. Assign a structure per employee and revise with effect dates.', 'snippet' => '12 structures · 21 components'],
            ['icon' => 'playlist_add_check', 'label' => 'Payroll runs', 'title' => 'Generate, review, approve, pay', 'body' => 'Generate the batch for the whole company, then review earnings, deductions and employer contributions per employee. Approve, then mark paid.', 'snippet' => 'RUN-2026-06 · COMPLETED'],
            ['icon' => 'request_quote', 'label' => 'Adjustments & loans', 'title' => 'Arrears, bonuses, recoveries, EMIs', 'body' => 'Attach one-off adjustments to a specific run. Loan EMIs schedule installments and deduct automatically until the balance clears.', 'snippet' => '3 adjustments · 11 EMIs'],
            ['icon' => 'description', 'label' => 'Reports', 'title' => 'Salary sheets and payslip PDFs', 'body' => 'Export the salary sheet for the run, or generate payslips one by one and in bulk. PF, ESI and GST details print on the payslip.', 'snippet' => '150 payslips · 1 salary sheet'],
        ];
        $lifecycle = [
            ['status' => 'draft', 'title' => 'Build the month', 'body' => 'Create the run for the company and period. Confirm headcount, structures, and that attendance is locked.'],
            ['status' => 'processing', 'title' => 'Calculate', 'body' => 'Earnings, deductions and employer contributions are computed from attendance and assigned compensation.'],
            ['status' => 'completed', 'title' => 'Finance reviews', 'body' => 'Controllers check nets, adjustments and loan EMIs. Audit logs show who changed what, and when.'],
            ['status' => 'locked', 'title' => 'Immutable', 'body' => 'The run is frozen with a version snapshot. Later corrections are new adjustments in a future run.'],
        ];
        $security = [
            ['icon' => 'domain', 'label' => 'Company scope', 'title' => 'Data is scoped per company', 'body' => 'Every employee, run and report belongs to a company. Multi-company groups keep payroll separate by design.'],
            ['icon' => 'key', 'label' => 'RBAC', 'title' => 'Role-based access', 'body' => 'Granular permissions such as payroll.manage, payroll.approve and reports.view decide who can generate, approve, or only read.'],
            ['icon' => 'receipt_long', 'label' => 'Audit log', 'title' => 'Centralized audit trail', 'body' => 'Structure changes, adjustments, approvals and lock events are recorded with actor, timestamp and previous value.'],
            ['icon' => 'lock_clock', 'label' => 'Snapshots', 'title' => 'Immutable payroll history', 'body' => 'Locked runs store a snapshot of the payslip lines used, so a reprint months later matches what finance approved.'],
            ['icon' => 'monitor_heart', 'label' => 'Observability', 'title' => 'Failed batches surface early', 'body' => 'Batch generation is instrumented. Failures are logged with the affected employees instead of failing silently mid-run.'],
        ];
        $monthClose = [
            ['day' => 'Jul 01', 'label' => 'Attendance locked', 'body' => 'Both plants closed. 6 gap days resolved.'],
            ['day' => 'Jul 02', 'label' => 'Run generated', 'body' => '150 employees, 12 structures applied.'],
            ['day' => 'Jul 03', 'label' => 'Run locked', 'body' => 'Approved by finance, 150 payslips issued.'],
            ['day' => 'Jul 09', 'label' => 'Arrears queued', 'body' => '2 late cases moved into the July run.'],
        ];
        $notFor = [
            ['no' => 'Not a filing service', 'body' => 'It calculates PF and ESI and prints them on payslips. It does not submit ECR or challans for you.'],
            ['no' => 'Not for 5-person teams', 'body' => 'The run lifecycle, RBAC and approvals only pay off from roughly 50 employees upward.'],
            ['no' => 'No payment rails yet', 'body' => 'You export the salary sheet and pay through your bank. There is no in-product bank transfer.'],
            ['no' => 'No non-India statutory setup', 'body' => 'Components, PF, ESI and payslip formats assume Indian payroll rules only.'],
        ];
        $aiChecks = [
            'Readiness checks before a run leaves DRAFT',
            'Attendance gaps by department and location',
            'Run status, adjustments, and loan EMI balances',
        ];
    @endphp

    <div id="top" class="min-h-screen bg-background">
        <header class="sticky top-0 z-50 border-b border-border bg-background/90 backdrop-blur">
            <nav class="section-shell flex h-[72px] items-center justify-between gap-6">
                <x-landing.logo href="#top" />
                <ul class="hidden items-center gap-7 lg:flex">
                    @foreach ($navLinks as $link)
                        <li>
                            <a href="{{ $link['href'] }}" class="text-[14px] text-muted-foreground transition-colors hover:text-primary">{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="flex items-center gap-2">
                    <x-landing.secondary-button :href="route('login')" class="hidden px-4 py-2.5 sm:inline-flex">Sign in</x-landing.secondary-button>
                    <x-landing.primary-button :href="route('sign-up')" class="px-4 py-2.5">Request a demo</x-landing.primary-button>
                </div>
            </nav>
        </header>

        <main>
            <section class="section-shell grid items-center gap-12 py-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:py-24">
                <div>
                    <x-landing.eyebrow>Enterprise Payroll &amp; HRMS</x-landing.eyebrow>
                    <h1 class="mt-4 text-4xl leading-[1.08] text-foreground sm:text-5xl lg:text-[3.35rem]">
                        Close payroll for 150 employees in
                        <span class="relative inline-block">
                            3 working days
                            <svg aria-hidden="true" viewBox="0 0 220 14" preserveAspectRatio="none" class="absolute -bottom-1.5 left-0 h-3 w-full text-primary">
                                <path d="M2 9.5C38 4.2 74 3.4 110 6.1c36 2.7 72 3.1 108 -3.3" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            </svg>
                        </span>
                        , not on the last night of the month.
                    </h1>
                    <p class="mt-6 max-w-xl text-[15px] leading-relaxed text-muted-foreground">
                        Lock attendance on day 1. Generate the run on day 2. Finance locks it on day 3. PF, ESI, loan
                        EMIs and arrears all live inside that one run, so nobody is comparing four spreadsheets at 11pm.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-landing.primary-button :href="route('sign-up')">Request a demo</x-landing.primary-button>
                        <x-landing.secondary-button href="#payroll">See how a payroll run works</x-landing.secondary-button>
                    </div>
                    <p class="mt-3 text-[12.5px] text-muted-foreground">
                        25 minutes, on your own salary structures. No card, and you don't have to move any data to look.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-2">
                        <x-landing.pill>
                            <x-landing.icon name="verified_user" class="text-[15px] text-primary" />
                            Built for Indian statutory payroll
                        </x-landing.pill>
                        <x-landing.pill>PF</x-landing.pill>
                        <x-landing.pill>ESI</x-landing.pill>
                        <x-landing.pill>GST on payslips</x-landing.pill>
                    </div>
                </div>
                @include('landing.partials.run-mock')
            </section>

            <section class="border-y border-border bg-card/70">
                <div class="section-shell py-14">
                    <p class="mono-label text-[10px] text-faint">For payroll operators, HR admins, and finance reviewers.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        @foreach ($stats as $stat)
                            <div class="ui-card" style="transform: rotate({{ $stat['rotate'] }})">
                                <x-landing.icon :name="$stat['icon']" class="text-[22px] text-primary" />
                                <p class="mt-4 font-[family-name:var(--font-display)] text-[24px] leading-none font-bold text-foreground tabular">{{ $stat['figure'] }}</p>
                                <p class="mono-label mt-2 text-[10px] text-faint">{{ $stat['label'] }}</p>
                                <p class="mt-3 text-[13px] leading-relaxed text-muted-foreground">{{ $stat['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="product" class="section-shell py-20 lg:py-24">
                <x-landing.section-heading
                    eyebrow="Why payroll slips"
                    title="The month doesn't break at calculation. It breaks at control."
                    body="Ask any payroll operator what went wrong last month and you'll usually hear one of these three. They aren't maths problems, they're control problems."
                />
                <div class="mt-10 grid gap-4 lg:grid-cols-3">
                    @foreach ($problems as $problem)
                        <div class="ui-card flex flex-col">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-muted">
                                <x-landing.icon :name="$problem['icon']" class="text-[21px] text-destructive" />
                            </span>
                            <h3 class="mt-5 text-[18px] leading-snug text-foreground">{{ $problem['pain'] }}</h3>
                            <div class="mt-4 border-t border-border pt-4">
                                <p class="mono-label text-[9px] text-primary">How PayrollPro handles it</p>
                                <p class="mt-2 text-[13px] leading-relaxed text-muted-foreground">{{ $problem['solve'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex flex-wrap items-center gap-3 rounded-[20px] border border-border bg-accent px-6 py-5">
                    <x-landing.icon name="lock" class="text-[20px] text-primary" />
                    <p class="text-[14px] text-foreground">A single payroll run per company and month, moving through</p>
                    <div class="flex flex-wrap gap-1.5">
                        <x-landing.status-badge status="draft" />
                        <x-landing.status-badge status="processing" />
                        <x-landing.status-badge status="completed" />
                        <x-landing.status-badge status="locked" />
                    </div>
                    <p class="text-[14px] text-foreground">Locked runs cannot be silently edited.</p>
                </div>
            </section>

            <section id="features" class="border-y border-border bg-card/70">
                <div class="section-shell py-20 lg:py-24">
                    <x-landing.section-heading
                        eyebrow="Product modules"
                        title="Everything a payroll operator touches in a month."
                        body="Six modules, one data model. Employees feed attendance, attendance feeds the run, the run produces payslips and salary sheets."
                    />
                    <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($modules as $module)
                            <article class="ui-card flex flex-col">
                                <span class="flex size-11 items-center justify-center rounded-xl bg-secondary">
                                    <x-landing.icon :name="$module['icon']" class="text-[21px] text-primary" />
                                </span>
                                <p class="mono-label mt-5 text-[9px] text-faint">{{ $module['label'] }}</p>
                                <h3 class="mt-2 text-[19px] leading-snug text-foreground">{{ $module['title'] }}</h3>
                                <p class="mt-3 flex-1 text-[13px] leading-relaxed text-muted-foreground">{{ $module['body'] }}</p>
                                <p class="tabular mt-5 rounded-xl border border-border bg-muted px-3 py-2 text-[11px] text-muted-foreground">{{ $module['snippet'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="payroll" class="section-shell py-20 lg:py-24">
                <x-landing.section-heading
                    eyebrow="How a run works"
                    title="Four states. One direction. No silent edits."
                    body="Every transition is written to the audit log with the actor and timestamp, and completed runs keep a version snapshot so finance can see who changed what."
                />
                <ol class="mt-10 grid gap-4 lg:grid-cols-4">
                    @foreach ($lifecycle as $index => $step)
                        <li class="ui-card relative flex flex-col">
                            <div class="flex items-center justify-between">
                                <x-landing.status-badge :status="$step['status']" />
                                <span class="tabular text-[12px] text-faint">0{{ $index + 1 }}</span>
                            </div>
                            <h3 class="mt-4 text-[18px] text-foreground">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-[13px] leading-relaxed text-muted-foreground">{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-6 flex flex-wrap items-center gap-4 rounded-[20px] border border-border bg-card px-6 py-5 shadow-soft">
                    <p class="mono-label text-[10px] text-faint">Employee payslip status</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-landing.status-badge status="draft" />
                        <x-landing.icon name="arrow_forward" class="text-[16px] text-faint" />
                        <x-landing.status-badge status="approved" icon="check" />
                        <x-landing.icon name="arrow_forward" class="text-[16px] text-faint" />
                        <x-landing.status-badge status="paid" icon="payments" />
                    </div>
                    <p class="text-[13px] text-muted-foreground">Status is never colour-only. Every badge carries its label.</p>
                </div>
            </section>

            <section id="ai" class="border-y border-border bg-card/70">
                <div class="section-shell grid items-center gap-12 py-20 lg:grid-cols-2 lg:py-24">
                    <div>
                        <x-landing.eyebrow>Payroll AI</x-landing.eyebrow>
                        <h2 class="mt-3 text-3xl leading-tight text-foreground sm:text-4xl">Ask Payroll AI. Verify before you approve.</h2>
                        <p class="mt-5 text-[15px] leading-relaxed text-muted-foreground">
                            Operators can ask about readiness, missing attendance, and run status without digging through six
                            screens. Answers point back to the employees and runs they came from.
                        </p>
                        <ul class="mt-6 space-y-3">
                            @foreach ($aiChecks as $check)
                                <li class="flex items-start gap-3 text-[14px] text-muted-foreground">
                                    <x-landing.icon name="check_circle" class="mt-0.5 text-[18px] text-success" />
                                    {{ $check }}
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-6 rounded-[16px] border border-warning-foreground/25 bg-warning px-4 py-3 text-[13px] text-warning-foreground">
                            Payroll AI can make mistakes. Verify important financial data before final approval.
                        </p>
                        <p class="mt-3 text-[12px] text-faint">
                            Payroll AI does not file statutory returns and does not pay salaries. Operators approve and pay.
                        </p>
                    </div>
                    @include('landing.partials.ai-mock')
                </div>
            </section>

            <section id="security" class="section-shell py-20 lg:py-24">
                <x-landing.section-heading
                    eyebrow="Security &amp; trust"
                    title="Controls a finance reviewer can check, not just read about."
                    body="PayrollPro is built for auditability first: scoped data, explicit permissions, and history that cannot be rewritten after lock."
                />
                <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($security as $item)
                        <div class="ui-card">
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 items-center justify-center rounded-xl bg-muted">
                                    <x-landing.icon :name="$item['icon']" class="text-[20px] text-primary" />
                                </span>
                                <p class="mono-label text-[9px] text-faint">{{ $item['label'] }}</p>
                            </div>
                            <h3 class="mt-4 text-[17px] leading-snug text-foreground">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-[13px] leading-relaxed text-muted-foreground">{{ $item['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="border-y border-border bg-card/70">
                <div class="section-shell py-20">
                    <div class="grid gap-10 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-center">
                        <div>
                            <x-landing.eyebrow>Month close</x-landing.eyebrow>
                            <h2 class="mt-3 text-3xl leading-tight text-foreground sm:text-[2.4rem]">A June close, day by day.</h2>
                            <p class="mt-4 max-w-md text-[14px] leading-relaxed text-muted-foreground">
                                This is the demo dataset in this build, not a customer story. We'd rather show you the boring
                                version than a chart nobody can check.
                            </p>
                            <p class="mt-6 max-w-sm rounded-[18px] border border-dashed border-primary/40 bg-card p-5 text-[13.5px] leading-relaxed text-muted-foreground" style="transform: rotate(-0.6deg)">
                                <span class="mono-label block text-[9px] text-primary">Note from the build</span>
                                <span class="mt-2 block">
                                    The two late arrear cases on Jul 09 are real behaviour, not a bug. Once a run is locked we
                                    push corrections into the next month instead of quietly editing history.
                                </span>
                            </p>
                        </div>
                        <ol class="grid gap-3 sm:grid-cols-2">
                            @foreach ($monthClose as $step)
                                <li class="ui-card">
                                    <p class="mono-label text-[9px] text-faint tabular">{{ $step['day'] }}</p>
                                    <p class="mt-3 text-[15px] font-medium text-foreground">{{ $step['label'] }}</p>
                                    <p class="mt-1 text-[13px] leading-relaxed text-muted-foreground">{{ $step['body'] }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </section>

            <section class="section-shell py-20 lg:py-24">
                <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <div>
                        <x-landing.eyebrow>Fit check</x-landing.eyebrow>
                        <h2 class="mt-3 text-3xl leading-tight text-foreground sm:text-[2.3rem]">Where PayrollPro stops.</h2>
                        <p class="mt-4 max-w-md text-[14px] leading-relaxed text-muted-foreground">
                            Better you hear it now than three months into a rollout.
                        </p>
                    </div>
                    <ul class="grid gap-3">
                        @foreach ($notFor as $item)
                            <li class="flex gap-3 rounded-[18px] border border-border bg-card p-5">
                                <x-landing.icon name="do_not_disturb_on" class="mt-0.5 text-[19px] text-destructive" />
                                <div>
                                    <p class="text-[15px] font-medium text-foreground">{{ $item['no'] }}</p>
                                    <p class="mt-1 text-[13px] leading-relaxed text-muted-foreground">{{ $item['body'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section id="demo" class="section-shell pb-20 lg:pb-24">
                <div class="rounded-[24px] border border-border bg-accent px-6 py-14 text-center shadow-soft sm:px-12">
                    <h2 class="mx-auto max-w-2xl text-3xl leading-tight text-foreground sm:text-[2.6rem]">
                        Close the month without rewriting last month.
                    </h2>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <x-landing.primary-button :href="route('sign-up')">Request a demo</x-landing.primary-button>
                        <x-landing.secondary-button :href="route('login')" class="bg-card">Sign in</x-landing.secondary-button>
                    </div>
                    <p class="mt-4 text-[12.5px] text-muted-foreground">
                        Takes about 45 seconds to request. We reply with 3 slots, not a sales sequence.
                    </p>
                    <p class="mono-label mt-6 text-[10px] text-faint">
                        Multi-company ready · Indian PF, ESI, and GST details on payslips
                    </p>
                </div>
            </section>
        </main>

        <footer class="border-t border-border bg-card">
            <div class="section-shell flex flex-col gap-8 py-12">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <x-landing.logo href="#top" />
                    <ul class="flex flex-wrap items-center gap-6">
                        <li><a href="#product" class="mono-label text-[10px] text-muted-foreground transition-colors hover:text-primary">Product</a></li>
                        <li><a href="#security" class="mono-label text-[10px] text-muted-foreground transition-colors hover:text-primary">Security</a></li>
                        <li><a href="{{ route('login') }}" class="mono-label text-[10px] text-muted-foreground transition-colors hover:text-primary">Sign in</a></li>
                    </ul>
                </div>
                <p class="border-t border-border pt-6 text-[12px] leading-relaxed text-faint">
                    PayrollPro · Enterprise Admin · proof-of-concept demo product. Screens and figures shown here are
                    sample data. No certifications or client claims are implied.
                </p>
            </div>
        </footer>
    </div>
</x-layouts.landing>
