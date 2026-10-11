### 10.5 Project Color Palette

The system will use a teal and cyan-based healthcare palette. This gives the interface a calm, professional look while still feeling distinct from a generic Bootstrap theme.

#### Default Palette

| Token | Color | Usage |
|---|---|---|
| Primary | `#0D7377` | Main buttons, active states, links, highlights |
| Primary Light | `#14A3A8` | Hover states, focus states, secondary highlights |
| Primary Dark | `#095B5E` | Active sidebar items, strong headers, important emphasis |
| Primary Transparent | `rgba(13, 115, 119, 0.1)` | Soft backgrounds, selected rows, subtle highlights |
| Secondary / Accent | `#4A90D9` | Secondary actions, charts, supporting visual accents |
| Sidebar Background | `#0A3D40` | Main sidebar navigation background |
| Sidebar Hover | `#0D7377` | Sidebar hover and active navigation state |
| Background Main | `#F4F6F9` | Main application background |
| Surface White | `#FFFFFF` | Cards, tables, forms, modals |
| Text Primary | `#2D3436` | Main text |
| Text Secondary | `#636E72` | Supporting text |
| Text Muted | `#707C80` | Help text, placeholders, less important labels |
| Border Color | `#DFE6E9` | Form borders, table borders, dividers |

#### Status and Alert Palette

| Status | Text / Icon Color | Background |
|---|---|---|
| Success | `#28A745` | `rgba(40, 167, 69, 0.1)` |
| Warning | `#FFC107` | `rgba(255, 193, 7, 0.15)` |
| Danger | `#DC3545` | `rgba(220, 53, 69, 0.1)` |
| Info | `#17A2B8` | `rgba(23, 162, 184, 0.1)` |

#### Login Page Gradient

| Token | Color | Usage |
|---|---|---|
| Gradient Start | `#0A3D40` | Login page background start |
| Gradient End | `#0D7377` | Login page background end |
| Floating Abstract Background Elements | `rgba(20, 163, 168, 0.15)` | Subtle teal glow elements on login page |

#### Suggested CSS Variables

```css
:root {
  --color-primary: #0D7377;
  --color-primary-light: #14A3A8;
  --color-primary-dark: #095B5E;
  --color-primary-soft: rgba(13, 115, 119, 0.1);

  --color-accent: #4A90D9;

  --color-sidebar: #0A3D40;
  --color-sidebar-hover: #0D7377;

  --color-bg: #F4F6F9;
  --color-surface: #FFFFFF;
  --color-text: #2D3436;
  --color-text-secondary: #636E72;
  --color-text-muted: #707C80;
  --color-border: #DFE6E9;

  --color-success: #28A745;
  --color-success-bg: rgba(40, 167, 69, 0.1);
  --color-warning: #FFC107;
  --color-warning-bg: rgba(255, 193, 7, 0.15);
  --color-danger: #DC3545;
  --color-danger-bg: rgba(220, 53, 69, 0.1);
  --color-info: #17A2B8;
  --color-info-bg: rgba(23, 162, 184, 0.1);

  --login-gradient-start: #0A3D40;
  --login-gradient-end: #0D7377;
  --login-bg-glow: rgba(20, 163, 168, 0.15);
}
```
