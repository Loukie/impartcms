# Premium Site Clone Agent Instructions

## Reference-Lock & Quality Mandate
- Always replicate the reference site's design language, structure, and content intent.
- Never default to generic templates or boilerplate layouts.
- If a reference/clone/screenshot exists, match its layout rhythm, navigation behavior, button treatment, density, and mood.
- If output looks template-like, halt and revise before finalizing.

## Frontend Design Director Standards
- Output must be premium, modern, intentional, and brand-specific.
- Define design tokens (`:root` CSS variables) for colors, type scale, spacing, radii, and shadows.
- Ensure responsive quality on desktop and mobile (320px+).
- Use purposeful animation only (nav transitions, section reveals).
- Keep accessibility and readability intact (contrast, focus visibility, readable text sizes).

## Logo & Imagery Placement
- Use the provided reference logo and images only in the exact locations and contexts as shown on the reference site.
- Never substitute, move, or add the logo or images in places not present in the reference.
- If placement is unclear, halt and request clarification or a screenshot.
- Diagnose and fix any code or asset errors before proceeding.

## Branding Defaults
- Do not hardcode a global brand palette or font family.
- Derive brand direction from explicit user-provided tokens, existing project/theme styles, or reference site cues.

## Navigation State Baseline
- Homepage top: transparent/overlay nav style.
- Homepage hover/scroll: transition to solid/light nav style.
- Inner pages: default to solid/light nav style.

## Clone Acceptance Checklist
- Desktop and mobile screenshots must look finished and professional.
- Navigation behavior is consistent across homepage and inner pages.
- No broken images/placeholders remain.
- Buttons, headings, and sections have consistent visual system and spacing rhythm.
- Result looks like a polished redesign of the reference, not a generic template.

## Workflow Best Practices
- Give intent + reference, not detailed specs.
- Reference-lock prevents template drift.
- Let the agent execute a complete opinionated pass.
- Use hard constraints, not suggestions.
- If output is generic, revise before final.

---

**Use these instructions as the default prompt for any GPT agent tasked with site cloning or frontend generation.**
**If the agent cannot match the reference, halt and request clarification.**
**Enforce these standards for every clone, every time.**
