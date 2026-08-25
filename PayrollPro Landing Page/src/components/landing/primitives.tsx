import type { ReactNode } from "react";
import { cn } from "@/lib/utils";

export function Icon({ name, className }: { name: string; className?: string }) {
  return (
    <span aria-hidden="true" className={cn("material-symbols-outlined", className)}>
      {name}
    </span>
  );
}

export function Eyebrow({ children, className }: { children: ReactNode; className?: string }) {
  return (
    <p className={cn("mono-label text-[11px] text-primary", className)}>{children}</p>
  );
}

type StatusTone = "draft" | "processing" | "completed" | "locked" | "approved" | "paid";

const statusTone: Record<StatusTone, string> = {
  draft: "bg-surface-3 text-muted-foreground border-border",
  processing: "bg-warning text-warning-foreground border-warning-foreground/25",
  completed: "bg-secondary text-primary border-primary/25",
  locked: "bg-foreground text-background border-foreground",
  approved: "bg-secondary text-primary border-primary/25",
  paid: "bg-success/12 text-success border-success/35",
};

export function StatusBadge({
  status,
  icon,
  className,
}: {
  status: StatusTone;
  icon?: string;
  className?: string;
}) {
  return (
    <span
      className={cn(
        "mono-label inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[10px]",
        statusTone[status],
        className,
      )}
    >
      {icon ? <Icon name={icon} className="text-[13px]" /> : null}
      {status}
    </span>
  );
}

export function Pill({ children, className }: { children: ReactNode; className?: string }) {
  return (
    <span
      className={cn(
        "mono-label inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-[10px] text-muted-foreground",
        className,
      )}
    >
      {children}
    </span>
  );
}

const btnBase =
  "mono-label inline-flex items-center justify-center gap-2 rounded-[20px] text-[12px] transition-colors disabled:opacity-60";

export function PrimaryButton({
  children,
  href = "#demo",
  className,
}: {
  children: ReactNode;
  href?: string;
  className?: string;
}) {
  return (
    <a
      href={href}
      className={cn(
        btnBase,
        "bg-primary px-5 py-3 text-primary-foreground shadow-primary hover:bg-primary-hover",
        className,
      )}
    >
      {children}
    </a>
  );
}

export function SecondaryButton({
  children,
  href = "#signin",
  className,
}: {
  children: ReactNode;
  href?: string;
  className?: string;
}) {
  return (
    <a
      href={href}
      className={cn(
        btnBase,
        "border border-border bg-secondary px-5 py-3 text-secondary-foreground hover:bg-surface-3",
        className,
      )}
    >
      {children}
    </a>
  );
}

export function SectionHeading({
  eyebrow,
  title,
  body,
  align = "left",
}: {
  eyebrow: string;
  title: string;
  body?: string;
  align?: "left" | "center";
}) {
  return (
    <div className={cn("max-w-2xl", align === "center" && "mx-auto text-center")}>
      <Eyebrow>{eyebrow}</Eyebrow>
      <h2 className="mt-3 text-3xl leading-tight text-foreground sm:text-4xl">{title}</h2>
      {body ? <p className="mt-4 text-[15px] leading-relaxed text-muted-foreground">{body}</p> : null}
    </div>
  );
}

export function Logo() {
  return (
    <a href="#top" className="flex items-center gap-3">
      <span className="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-primary">
        <Icon name="payments" className="text-[22px]" />
      </span>
      <span className="leading-tight">
        <span className="block font-[family-name:var(--font-display)] text-[17px] font-bold text-foreground">
          PayrollPro
        </span>
        <span className="mono-label block text-[9px] text-faint">Enterprise Admin</span>
      </span>
    </a>
  );
}
