# Post-Clone Audit & Fix Checklist for Site Generation Agents

## 1. Logo Placement
- [ ] The correct reference logo is placed in the navigation on every page, exactly matching the reference site.
- [ ] No random or substitute images are used for the logo.
- [ ] If logo placement is unclear, halt and request clarification or a screenshot.

## 2. Layout Consistency
- [ ] All pages (including smart-lighting-automation and contact) use a full-width layout, with no max-width or container constraints unless explicitly present in the reference.
- [ ] No page uses a boxed or constrained layout unless the reference site does.

## 3. Color & Design Tokens
- [ ] Only colors, gradients, and tokens visibly used in the reference site UI are present in the output.
- [ ] No unused CSS presets or invented colors are included.

## 4. Navigation & Structure
- [ ] Navigation matches the reference site’s structure, state transitions, and placement.
- [ ] No extra or missing navigation elements.

## 5. Final Quality Gate
- [ ] Desktop and mobile screenshots would both look finished and professional.
- [ ] No broken images, placeholders, or generic template artifacts remain.
- [ ] Buttons, headings, and sections have consistent visual system and spacing rhythm.

---

**If any item fails, the agent must automatically fix the issue before finalizing the output.**
**If a fix is not possible, halt and request clarification.**
