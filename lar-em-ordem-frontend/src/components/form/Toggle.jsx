import { Description, Field, Label, Switch } from "@headlessui/react";

/**
 * An on/off switch with a label and a description, for settings that apply as soon as they change.
 */
export function Toggle({ checked, onChange, label, description, disabled = false }) {
  return (
    <Field disabled={disabled} className="flex items-start justify-between gap-4">
      <div className="flex flex-col gap-1">
        <Label className="text-sm font-medium text-text">{label}</Label>
        {description && <Description className="text-sm text-muted">{description}</Description>}
      </div>
      <Switch
        checked={checked}
        onChange={onChange}
        className="group relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full bg-border outline-none transition focus-visible:ring-4 focus-visible:ring-navy/10 data-checked:bg-navy data-disabled:cursor-not-allowed data-disabled:opacity-60"
      >
        <span
          aria-hidden="true"
          className="size-4 translate-x-1 rounded-full bg-white shadow transition group-data-checked:translate-x-6"
        />
      </Switch>
    </Field>
  );
}