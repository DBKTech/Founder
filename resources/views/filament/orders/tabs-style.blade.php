<style>
/* Inline Orders Tabs Styling (NO VITE) */
.orders-tab { --ot: 99 102 241; }

.orders-tab--all       { --ot: 107 114 128; }
.orders-tab--completed { --ot: 34 197 94; }
.orders-tab--approved  { --ot: 59 130 246; }
.orders-tab--pending   { --ot: 245 158 11; }
.orders-tab--move      { --ot: 168 85 247; }
.orders-tab--rejected  { --ot: 239 68 68; }
.orders-tab--cancelled { --ot: 100 116 139; }
.orders-tab--returned  { --ot: 20 184 166; }
.orders-tab--draft     { --ot: 113 113 122; }
.orders-tab--unprint   { --ot: 249 115 22; }

.orders-tab button,
.orders-tab a {
  border-radius: 9999px !important;
  padding: 6px 12px !important;
}

.orders-tab [aria-selected="true"],
.orders-tab [data-active="true"] {
  color: rgb(var(--ot)) !important;
  background: rgba(var(--ot), .12) !important;
  border: 1px solid rgba(var(--ot), .35) !important;
}

.orders-tab [aria-selected="true"] .fi-badge,
.orders-tab [data-active="true"] .fi-badge {
  background: rgba(var(--ot), .18) !important;
  color: rgb(var(--ot)) !important;
}
</style>