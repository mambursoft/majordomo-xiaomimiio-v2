# Architecture notes — Xiaomi miIO v2

This package keeps the original database schema for compatibility but separates responsibilities:

- transport clients own network I/O and timeouts;
- services own validation and state transitions;
- templates own presentation only;
- language files own all user-visible strings.

Known limitation: the upstream monolithic class remains the reference for behaviour. Full extraction is iterative; this v2 package applies the safety and UI layers first.