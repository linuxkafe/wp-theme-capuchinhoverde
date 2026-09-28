# DESIGN

> Reconciliado com o código em 2026-09-27 (ticket T002). As duas paletas abaixo **não coexistem** —
> ver "Conflito aberto" e o ticket T006.

## Identidade visual

Capuchinho Verde é um port do tema **Cafeteria v1.7** (ALETheme) para uma clínica de saúde
estética. A identidade visual é, portanto, a do Cafeteria — não uma identidade nova.

## Paleta em produção (origem: Cafeteria v1.7, `assets/css/legacy/`)

Extraída do CSS vendorizado, por frequência de uso:

| Token | Hex | Ocorrências | Papel inferido |
|-------|-----|-------------|----------------|
| `--cg-clay` | `#5e3c3d` | 70 | cor primária / botões |
| `--cg-paper` | `#ffffff` / `#fff` | 98 | superfícies |
| `--cg-mist` | `#b1dae6` | 82 | fundo suave, secundário |
| `--cg-blush` | `#ffbdb8` | 18 | acento / hover |
| `--cg-cream` | `#f0ece3` | 6 | fundo de página |
| `--cg-stone` | `#949494` | 8 | texto secundário |

Tema claro. `body { background: transparent }` em `assets/css/legacy/general.css:147`, com
superfícies brancas a `general.css:368` e `general.css:392`.

## Paleta AES padrão (declarada, não aplicada)

O bloco `:root` em `style.css:19-27` define um set **escuro** distinto:

```
--cg-bg: #0a0a0a    --cg-text: #fafafa    --cg-accent: #22c55e
```

Verde de capuchinho (`--cg-accent`) e fundo quase preto. Vem do template de tema AES e **não
corresponde** à identidade Cafeteria. Apenas `--cg-accent` é usado por `style.css:30` (cor de
links); os restantes declaram tokens que o CSS vendorizado não consome.

## Conflito aberto — T006

O bloco `:root` escuro e o CSS legacy claro travam no mesmo `body`. O commit `72a0b4f`
("resolve black page issue - remove conflicting body styles") é evidência de que este conflito já
causou um defeito visível. **Decidir qual paleta é a canónica é trabalho de T006** — não de T002.
Até lá, não editar nenhuma das duas paletas.

## Tipografia

- Legado: fontes do Cafeteria em `assets/css/legacy/`.
- Base: `Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif` (`style.css:30`).

## Layout

- `theme.json` v2: controlos de largura de conteúdo e paleta de editor são a fonte de verdade do
  alinhamento no editor.
- Grade responsiva herdada de `assets/css/legacy/responsive.css`.

## Componentes

Header minimal, navegação sticky, e as secções de paridade servidas por `inc/ale-compat.php`:
slider, services, gallery (filtro Isotope), team, menu/price, events, menus duplos no header,
drawer móvel, formulário de contacto, Google Maps, ícones sociais.
