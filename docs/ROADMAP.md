# ROADMAP

> Reconciliado com o código em 2026-09-27 (ticket T002). Itens já presentes no repositório
> saíram do Backlog. O que está listado aqui está **de facto** por fazer.

## Concluído v1.0.0

- Estrutura base do tema e registo dos CPTs `cg_menu`, `cg_gallery`, `cg_event` (`inc/post-types.php`)
- `theme.json` v2 e suporte a blocos (Gutenberg)
- Port de paridade Cafeteria v1.7: `inc/ale-compat.php` + `assets/{css,js}/legacy/`
- Templates de CPT: `archive-cg_{menu,gallery,event}.php`, `single-cg_{menu,gallery,event}.php`
- Padrão de bloco `patterns/menu-card.php`
- Esqueleto E2E com Playwright (`playwright.config.ts`, `tests/e2e/smoke.spec.ts`)
- Internacionalização: `languages/capuchinhoverde.pot`
- Infraestrutura AES: `aes/`, `Makefile`, `.aes/hooks/`, `CLAUDE.md`, `make check`

## Backlog

| Item | Impact | Effort | Priority | Status |
|------|--------|--------|----------|--------|
| Corrigir `Requires at least` para 6.5+ em `style.css` (T005) | Support-level change; aligns header with the block-first target | S | high | pending |
| Alargar `make check` com detecção de drift do ROADMAP vs. git (T003 follow-up) | Prevents the exact staleness T002 just fixed | M | medium | pending |
| Decidir análise estática (PHPStan / composer) — T004 | Closes the largest blind spot in `inc/ale-compat.php` | L | low | blocked |
| Testes E2E além dos dois smoke specs | Current coverage proves almost nothing about parity | M | medium | pending |
| Traduções PT-PT (`.po` a partir do `.pot`) | Site is PT-facing; `.pot` exists but is untranslated | M | medium | pending |
| CI (`.github/workflows/`) a correr `make check` | Nothing runs the gate automatically today | S | medium | pending |
| Excluir `aes/`, `Makefile`, `tests/`, `node_modules/` do zip de release | Ships dev scaffolding to production | S | high | pending |

## Fora de âmbito (registado, não promising)

- Suporte a PHP < 8.2 (docs corrigidas; a direcção foi decidida em T002)
- Reescrita do `assets/{css,js}/legacy/` — é fonte de port, não código nosso
