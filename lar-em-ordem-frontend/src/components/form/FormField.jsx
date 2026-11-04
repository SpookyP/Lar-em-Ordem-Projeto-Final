import { Description, Field, Label } from "@headlessui/react";
import { cx } from "./styles";

/**
 * Label, the control, then the error (or the hint).
 * Headless UI links the label and the messages to the control for screen readers.
 */
export function FormField({ label, hint, error, required = false, className, children }) {
  return (
    <Field className={cx("flex flex-col gap-1.5", className)}>
      {label && (
        <Label className="text-sm font-medium text-text">
          {label}
          {required && (
            <span className="ml-0.5 text-red" aria-hidden="true">
              *
            </span>
          )}
        </Label>
      )}
      {children}
      {error ? (
        <Description className="text-xs text-red" role="alert">
          {error}
        </Description>
      ) : hint ? (
        <Description className="text-xs text-muted">{hint}</Description>
      ) : null}
    </Field>
  );
}