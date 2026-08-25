import { Icon, StatusBadge } from "./primitives";

const rows = [
  { code: "EMP-1042", name: "Anjali Deshpande", dept: "Operations", net: "82,410.00", status: "paid" as const },
  { code: "EMP-1078", name: "Rahul Menon", dept: "Finance", net: "1,14,250.00", status: "approved" as const },
  { code: "EMP-1103", name: "Farhan Qureshi", dept: "Plant, Pune", net: "46,980.00", status: "approved" as const },
  { code: "EMP-1129", name: "Sneha Iyer", dept: "People Ops", net: "68,500.00", status: "paid" as const },
  { code: "EMP-1156", name: "Vikram Singh", dept: "Plant, Nashik", net: "39,120.00", status: "draft" as const },
];

const adjustments = [
  { type: "ARREARS", employee: "Rahul Menon", amount: "+ 12,000.00" },
  { type: "LOAN EMI", employee: "Farhan Qureshi", amount: "− 4,500.00" },
  { type: "BONUS", employee: "Sneha Iyer", amount: "+ 15,000.00" },
];

export function RunMock() {
  return (
    <div className="overflow-hidden rounded-[24px] border border-border bg-card shadow-soft">
      {/* Window header */}
      <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-surface-2 px-5 py-4">
        <div>
          <p className="mono-label text-[9px] text-faint">Payroll run · June 2026</p>
          <h3 className="mt-1 text-[17px] text-foreground">Monthly payroll run · RUN-2026-06</h3>
        </div>
        <div className="flex flex-wrap items-center gap-1.5">
          <StatusBadge status="draft" />
          <StatusBadge status="processing" />
          <StatusBadge status="completed" icon="check" />
          <StatusBadge status="locked" icon="lock" />
        </div>
      </div>

      {/* Readiness banner */}
      <div className="flex flex-wrap items-center gap-3 border-b border-border bg-accent px-5 py-3.5">
        <Icon name="fact_check" className="text-[20px] text-primary" />
        <p className="text-[13px] text-foreground">
          <span className="tabular font-medium">142/150</span> employees ready for June 2026
        </p>
        <span className="mono-label rounded-full border border-warning-foreground/25 bg-warning px-2.5 py-1 text-[10px] text-warning-foreground">
          8 attendance gaps
        </span>
      </div>

      {/* Grid */}
      <div className="px-2 pt-2 pb-1 sm:px-4">
        <div className="grid grid-cols-[minmax(0,1fr)_96px_88px] items-center gap-2 border-b border-border px-2 py-2 sm:grid-cols-[100px_minmax(0,1.5fr)_minmax(0,1fr)_110px_92px]">
          {["Code", "Employee", "Department", "Net pay", "Status"].map((h, i) => (
            <span
              key={h}
              className={`mono-label text-[9px] text-faint ${i === 3 ? "text-right sm:text-right" : ""} ${
                i === 0 || i === 2 ? "hidden sm:block" : ""
              }`}
            >
              {h}
            </span>
          ))}
        </div>
        {rows.map((r) => (
          <div
            key={r.code}
            className="grid grid-cols-[minmax(0,1fr)_96px_88px] items-center gap-2 border-b border-border/60 px-2 py-2.5 last:border-b-0 sm:grid-cols-[100px_minmax(0,1.5fr)_minmax(0,1fr)_110px_92px]"
          >
            <span className="tabular hidden text-[11px] text-faint sm:block">{r.code}</span>
            <span className="truncate text-[13px] font-medium text-foreground">{r.name}</span>
            <span className="hidden truncate text-[12px] text-muted-foreground sm:block">{r.dept}</span>
            <span className="tabular text-right text-[12px] text-foreground">₹{r.net}</span>
            <span className="flex justify-end sm:justify-start">
              <StatusBadge status={r.status} />
            </span>
          </div>
        ))}

      </div>

      {/* Adjustments */}
      <div className="border-t border-border bg-muted px-5 py-4">
        <div className="flex items-center justify-between">
          <p className="mono-label text-[9px] text-faint">Adjustments attached to run</p>
          <p className="mono-label text-[9px] text-primary">3 lines</p>
        </div>
        <div className="mt-3 grid gap-2 sm:grid-cols-3">
          {adjustments.map((a) => (
            <div key={a.type} className="rounded-xl border border-border bg-card px-3 py-2.5">
              <p className="mono-label text-[9px] text-faint">{a.type}</p>
              <p className="mt-1 truncate text-[12px] text-muted-foreground">{a.employee}</p>
              <p
                className={`tabular mt-1 text-[13px] ${
                  a.amount.startsWith("+") ? "text-success" : "text-destructive"
                }`}
              >
                {a.amount}
              </p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
