<div align="center">

<h1>Dot.Tasks</h1>

<p>AI-powered task management — break down complex tasks into subtasks with time estimates and manage them on a drag-and-drop kanban board.</p>

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-4E56A6?style=flat-square)](https://livewire.laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Tests](https://img.shields.io/badge/tests-37%20passing-brightgreen?style=flat-square)](tests/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

</div>

---

## Overview

Dot.Tasks is the task management platform in the Dot ecosystem. Organise work into task lists, break complex tasks into AI-generated subtasks with time estimates, and track everything on a 4-column kanban board with a slide-out task detail drawer.

---

## Features

- **AI Task Breakdown** — describe a task, Claude generates subtasks with time estimates
- **4-column Kanban** — Todo → In Progress → Review → Done with drag-and-drop
- **Subtasks** — self-referencing task hierarchy with unlimited nesting
- **Task detail drawer** — slide-out panel for comments, labels, priority, due date
- **Labels** — colour-coded tags for filtering and categorisation
- **Task lists** — group tasks into named lists per team or project
- **Ecosystem SSO** — authenticate from InfoDot with a single click

---

## Domain Model

```
TaskList → Tasks (self-referencing parent/subtask via parent_id)
        → Labels (many-to-many)
        → TaskComments
        → AiBreakdownLogs
Team    → TaskLists
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 + PHP 8.4 |
| Frontend | Livewire 3 + Alpine.js + Tailwind CSS |
| Auth | Jetstream 5 + Sanctum (ecosystem SSO) |
| Database | PostgreSQL 16 (shared infodot instance) |
| AI | Anthropic Claude API (mock fallback when key absent) |
| WebSockets | Laravel Reverb |

---

## Quick Start

```bash
git clone https://github.com/sakhileb/Dot.Tasks.git && cd Dot.Tasks
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate && npm run dev & php artisan serve
```

```bash
bash bin/test.sh   # 37 passing, 0 failed, 7 skipped
```

---

## Part of the Dot Ecosystem

Dot.Tasks connects to [InfoDot](https://github.com/sakhileb/InfoDot) — the central hub. Log in to InfoDot once and navigate here without re-authenticating via `/auth/ecosystem`.

---

MIT — © SK Digital / BluPin Incorporated
