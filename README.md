<div align="center">

<img src="docs/logo.svg" alt="Dot.Tasks" width="320" />

<br /><br />

**Break down complex work into subtasks with time estimates and track everything on kanban.**

<br />

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white) ![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white) ![Livewire](https://img.shields.io/badge/Livewire-3-FB70A9?style=flat-square) ![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=flat-square&logo=postgresql&logoColor=white)

<br /><br />

**Part of the [InfoDot Ecosystem](https://github.com/sakhileb/InfoDot)** &nbsp;·&nbsp; `tasks.infodot.app`

</div>

---

## What is Dot.Tasks?

Dot.Tasks is the personal and team task management platform in the InfoDot ecosystem. AI decomposes complex goals into subtasks with time estimates; a flexible kanban board and list view let every team member manage their workload their way.

## Core Features

- AI task decomposition — break any goal into actionable subtasks, with time estimates per subtask (falls back to a fixed mock breakdown when `ANTHROPIC_API_KEY` is unset)
- Drag-and-drop kanban board with search/filter by title or description
- Task assignment, scoped server-side to the task's team
- In-app notification bell (task assigned, new comment, task due soon) backed by Laravel's `database` notification channel
- Labels, priorities, and due dates
- Dashboard KPIs: total lists/tasks, in progress, completed, due today, overdue, completed this week
- Class-based dark/light theme toggle
- Ecosystem SSO from InfoDot hub

> Recurring tasks/schedules, calendar view, and a built-in Pomodoro timer are **not implemented** — see `wiki.md` §8 for the full gap list against the original aspirational feature set.

## Domain Models

- **TaskList** — a board; owns many tasks
- **Task** — unit of work with status, priority, optional assignee and due date; subtasks are just tasks with a `parent_id`
- **Label** — colour-coded category tag, many-to-many with tasks
- **TaskComment** — discussion on a task
- **AiBreakdownLog** — prompt/response audit trail for each AI decomposition call

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Language | PHP 8.4 |
| Frontend | Livewire 3 · Alpine.js 3 · Tailwind CSS |
| Database | PostgreSQL 16 (shared across ecosystem) |
| Realtime | Laravel Reverb |
| Auth | Laravel Sanctum (InfoDot SSO) |
| AI | Anthropic Claude (`claude-sonnet-4-6`) |
| Storage | AWS S3 / Local (Flysystem) |
| Search | Laravel Scout · Meilisearch |
| Queue | Redis · Laravel Horizon |

## Quick Start

```bash
git clone https://github.com/sakhileb/Dot.Tasks.git
cd Dot.Tasks
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate
php artisan serve
```

> **Ecosystem SSO:** Set `DB_*` env vars to the shared InfoDot PostgreSQL instance and `APP_URL=https://tasks.infodot.app`. Users authenticated through InfoDot gain access automatically via Sanctum handoff tokens.

## Ecosystem

**Dot.Tasks** is one of **21 platforms** in the InfoDot ecosystem, connected via shared PostgreSQL and Sanctum SSO. Visit [InfoDot](https://github.com/sakhileb/InfoDot) to explore the full platform map.

## License

MIT © [SK Digital / BluPin Incorporated](https://github.com/sakhileb)
