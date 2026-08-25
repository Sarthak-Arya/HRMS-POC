import { Icon } from "./primitives";

const suggestions = [
  { icon: "checklist", text: "Which employees are missing attendance for June?" },
  { icon: "query_stats", text: "Is RUN-2026-06 ready to move to COMPLETED?" },
  { icon: "receipt_long", text: "Show loan EMIs attached to this run." },
];

export function AiMock() {
  return (
    <div className="overflow-hidden rounded-[24px] border border-border bg-card shadow-soft">
      <div className="flex items-center justify-between border-b border-border bg-surface-2 px-5 py-4">
        <div className="flex items-center gap-3">
          <span className="flex size-9 items-center justify-center rounded-xl border border-border bg-card">
            <Icon name="auto_awesome" className="text-[19px] text-primary" />
          </span>
          <div>
            <p className="text-[14px] font-medium text-foreground">Payroll AI</p>
            <p className="mono-label text-[9px] text-faint">Operator assistant</p>
          </div>
        </div>
        <span className="mono-label rounded-full border border-success/35 bg-success/12 px-2.5 py-1 text-[10px] text-success">
          online
        </span>
      </div>

      <div className="px-5 py-7">
        <h3 className="bg-gradient-to-r from-primary to-success bg-clip-text text-2xl text-transparent">
          How can I help with June payroll?
        </h3>
        <p className="mt-2 text-[13px] text-muted-foreground">
          Ask about readiness, attendance gaps, or run status. I read the current run. I do not change it.
        </p>

        <div className="mt-5 grid gap-2">
          {suggestions.map((s) => (
            <button
              key={s.text}
              type="button"
              className="flex items-center gap-3 rounded-xl border border-border bg-muted px-3.5 py-3 text-left transition-colors hover:bg-surface-3"
            >
              <Icon name={s.icon} className="text-[18px] text-primary" />
              <span className="text-[13px] text-muted-foreground">{s.text}</span>
            </button>
          ))}
        </div>

        <div className="mt-5 flex gap-3 rounded-xl border border-border bg-surface-2 px-3.5 py-3">
          <span className="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
            <Icon name="auto_awesome" className="text-[15px]" />
          </span>
          <p className="text-[13px] leading-relaxed text-muted-foreground">
            8 employees have unapproved attendance in Plant, Nashik. Close the attendance lock for June 2026
            before you move <span className="mono-label text-[11px] text-foreground">RUN-2026-06</span> to{" "}
            <span className="mono-label text-[11px] text-foreground">PROCESSING</span>.
          </p>
        </div>
      </div>

      <p className="border-t border-border bg-muted px-5 py-3 text-[11px] text-faint">
        Payroll AI can make mistakes. Verify important financial data before final approval.
      </p>
    </div>
  );
}
