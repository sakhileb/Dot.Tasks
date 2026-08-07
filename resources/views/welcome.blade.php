<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dot.Tasks — AI task breakdown and kanban board</title>
        <meta name="description" content="Turn a goal into subtasks with time estimates, then run the work on a kanban board or list view — solo or with your team.">

        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@500;600;700;800&family=Karla:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --paper: #faf6ec;
                --paper-deep: #f1e8d2;
                --ink: #241c0c;
                --ink-soft: #6b5d42;
                --mustard: #f1c62e;
                --amber: #f2a803;
                --amber-ink: #8a5800;
                --line: rgba(36, 28, 12, 0.12);
                --font-display: 'Schibsted Grotesk', system-ui, sans-serif;
                --font-body: 'Karla', system-ui, sans-serif;
                --font-mono: 'Space Mono', ui-monospace, monospace;
                --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            }
            html { background: var(--paper); }
            body { font-family: var(--font-body); background: var(--paper); color: var(--ink); }
            .font-display { font-family: var(--font-display); }
            .font-mono { font-family: var(--font-mono); }

            .press { transition: transform 160ms var(--ease-out); }
            .press:active { transform: scale(0.97); }

            @media (prefers-reduced-motion: no-preference) {
                .reveal {
                    opacity: 0;
                    transform: translateY(14px);
                    transition: opacity 600ms var(--ease-out), transform 600ms var(--ease-out);
                }
                .reveal.is-visible { opacity: 1; transform: translateY(0); }
            }
            @media (prefers-reduced-motion: reduce) {
                .reveal { opacity: 1; transform: none; }
            }

            @media (hover: hover) and (pointer: fine) {
                .row-hover:hover { background: rgba(36, 28, 12, 0.025); }
                .link-underline { background-size: 0% 1px; }
                .link-underline:hover { background-size: 100% 1px; }
            }
            .link-underline {
                background-image: linear-gradient(currentColor, currentColor);
                background-position: 0 100%;
                background-repeat: no-repeat;
                transition: background-size 220ms var(--ease-out);
            }
        </style>
    </head>
    <body class="antialiased">

        <!-- Nav -->
        <header
            x-data="{ scrolled: false, mobileMenuOpen: false }"
            @scroll.window="scrolled = window.pageYOffset > 24"
            :class="scrolled ? 'bg-[#faf6ec]/95 backdrop-blur-md border-b border-[var(--line)]' : 'border-b border-transparent'"
            class="fixed top-0 left-0 right-0 z-50 transition-colors duration-300"
        >
            <nav class="max-w-[1400px] mx-auto px-5 sm:px-8 py-3 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5 press">
                    <img src="{{ asset('images/logo-outlined.png') }}" alt="Dot.Tasks" class="h-16 sm:h-20 w-auto">
                </a>

                <div class="hidden md:flex items-center gap-8 font-mono text-[13px] tracking-wide uppercase text-[var(--ink-soft)]">
                    <a href="#features" class="link-underline hover:text-[var(--ink)] pb-0.5">What it does</a>
                    <a href="#capabilities" class="link-underline hover:text-[var(--ink)] pb-0.5">Ecosystem</a>
                </div>

                @if (Route::has('login'))
                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="press flex items-center gap-2 px-5 py-2.5 bg-[var(--amber)] hover:bg-[var(--mustard)] text-[var(--ink)] text-sm font-display font-semibold rounded-lg transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:block text-sm font-medium text-[var(--ink-soft)] hover:text-[var(--ink)] transition-colors">
                                Sign in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="press px-5 py-2.5 bg-[var(--amber)] hover:bg-[var(--mustard)] text-[var(--ink)] text-sm font-display font-semibold rounded-lg transition-colors">
                                    Create account
                                </a>
                            @endif
                        @endauth

                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden press p-2 -mr-2 text-[var(--ink)]" aria-label="Toggle menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7h16M4 12h16M4 17h16"></path>
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif
            </nav>

            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="md:hidden border-t border-[var(--line)] bg-[#faf6ec]"
                 style="display: none;">
                <div class="flex flex-col px-5 py-4 gap-1 font-mono text-sm uppercase tracking-wide">
                    <a href="#features" class="px-3 py-2.5 text-[var(--ink-soft)] hover:text-[var(--ink)]">What it does</a>
                    <a href="#capabilities" class="px-3 py-2.5 text-[var(--ink-soft)] hover:text-[var(--ink)]">Ecosystem</a>
                    @guest
                        <a href="{{ route('login') }}" class="px-3 py-2.5 text-[var(--ink-soft)] hover:text-[var(--ink)]">Sign in</a>
                    @endguest
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative min-h-[100dvh] flex items-end overflow-hidden">
            <!-- Photo: notebook, fountain pen, and glasses, by David Travis, unsplash.com/photos/brown-fountain-pen-on-notebook-5bYxXawHOQg -->
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1517842645767-c639042777db?q=80&w=2400&auto=format&fit=crop');"></div>
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(250,246,236,0.30) 0%, rgba(250,246,236,0.62) 46%, #faf6ec 93%);"></div>
            <div class="absolute inset-0" style="background: linear-gradient(90deg, #faf6ec 0%, rgba(250,246,236,0.78) 34%, rgba(250,246,236,0.32) 66%, rgba(250,246,236,0.1) 100%);"></div>

            <!-- Checkbox silhouette — line-art nod to the checkbox-and-check icon in the real Dot.Tasks mark -->
            <svg class="hidden lg:block absolute right-[6%] bottom-0 h-[62%] w-auto opacity-[0.16] pointer-events-none" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="26" y="26" width="148" height="148" rx="20" stroke="#241c0c" stroke-width="6"/>
                <path d="M58 104L88 137L146 64" stroke="#241c0c" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            <div class="relative z-10 max-w-[1400px] mx-auto px-5 sm:px-8 pt-32 pb-16 sm:pb-20 w-full">
                <div class="max-w-2xl reveal" data-reveal>
                    <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--amber-ink)] mb-6">
                        Goal &rarr; subtasks &rarr; board
                    </p>

                    <h1 class="font-display font-bold text-4xl sm:text-5xl lg:text-6xl leading-[1.05] tracking-tight text-[var(--ink)] mb-6">
                        Type the goal.<br>Work the board.
                    </h1>

                    <p class="text-lg text-[var(--ink-soft)] leading-relaxed max-w-xl mb-10">
                        Dot.Tasks turns a goal into subtasks with time estimates, then hands you a kanban board and list view to run the work — solo or with your team.
                    </p>

                    @guest
                        <div class="flex flex-wrap items-center gap-4">
                            <a href="{{ route('register') }}" class="press px-7 py-3.5 bg-[var(--amber)] hover:bg-[var(--mustard)] text-[var(--ink)] font-display font-semibold rounded-lg transition-colors">
                                Create account
                            </a>
                            <a href="#features" class="press flex items-center gap-2 px-7 py-3.5 text-[var(--ink)] font-medium rounded-lg border border-[var(--line)] hover:border-[var(--ink-soft)] transition-colors">
                                See how it works
                            </a>
                        </div>
                    @endguest
                </div>
            </div>

            <!-- Live data strip — a capability list, not a fabricated metric -->
            <div class="relative z-10 w-full border-t border-[var(--line)] bg-[#faf6ec]/85 backdrop-blur-sm">
                <div class="max-w-[1400px] mx-auto px-5 sm:px-8 py-4 flex flex-wrap gap-x-8 gap-y-2 font-mono text-[11px] tracking-[0.14em] uppercase text-[var(--ink-soft)]">
                    <span>AI breakdown</span>
                    <span class="text-[var(--amber-ink)]">&middot;</span>
                    <span>Kanban + list view</span>
                    <span class="text-[var(--amber-ink)]">&middot;</span>
                    <span>Subtasks</span>
                    <span class="text-[var(--amber-ink)]">&middot;</span>
                    <span>Priority &amp; due dates</span>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="py-24 sm:py-28 px-5 sm:px-8">
            <div class="max-w-[1400px] mx-auto">
                <div class="max-w-xl mb-16 reveal" data-reveal>
                    <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--amber-ink)] mb-4">What it does</p>
                    <h2 class="font-display font-semibold text-3xl sm:text-4xl text-[var(--ink)] leading-tight">
                        The pieces that make up a task
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 border-t border-[var(--line)]">
                    @php
                        $features = [
                            ['tag' => 'Breakdown', 'title' => 'AI task breakdown', 'body' => 'Give it a goal and it returns a subtask list with time estimates, powered by Claude — every call logged to a full audit trail of prompt, response, and token count.'],
                            ['tag' => 'Board', 'title' => 'Kanban board', 'body' => 'Drag tasks across todo, in progress, review, and done. Search and filter by title or description as the board grows.'],
                            ['tag' => 'Subtasks', 'title' => 'Subtasks, not a second system', 'body' => 'Subtasks are just tasks with a parent — AI-generated or added by hand, tracked in the same list, no separate tool to learn.'],
                            ['tag' => 'Priority', 'title' => 'Priority &amp; due dates', 'body' => 'Every task carries a priority from low to urgent, an optional due date, and an estimated duration — the numbers the dashboard\'s counts are built from.'],
                            ['tag' => 'Labels', 'title' => 'Colour-coded labels', 'body' => 'Team-scoped labels tag tasks by type or project, and a task can carry as many as it needs.'],
                            ['tag' => 'Comments', 'title' => 'A flat comment thread', 'body' => 'Discussion stays on the task it\'s about — one thread per task, no nested replies to lose track of.'],
                        ];
                    @endphp
                    @foreach ($features as $i => $f)
                        <div class="row-hover border-b border-[var(--line)] {{ $i % 2 === 0 ? 'md:border-r' : '' }} px-1 py-8 sm:py-10 transition-colors reveal" data-reveal>
                            <p class="font-mono text-[11px] tracking-[0.14em] uppercase text-[var(--amber-ink)] mb-3">{{ $f['tag'] }}</p>
                            <h3 class="font-display font-semibold text-xl text-[var(--ink)] mb-2.5">{!! $f['title'] !!}</h3>
                            <p class="text-[var(--ink-soft)] leading-relaxed max-w-md">{!! $f['body'] !!}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Capabilities -->
        <section id="capabilities" class="py-24 sm:py-28 px-5 sm:px-8 bg-[var(--paper-deep)] border-y border-[var(--line)]">
            <div class="max-w-[1400px] mx-auto">
                <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] gap-12 lg:gap-20">
                    <div class="reveal" data-reveal>
                        <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--amber-ink)] mb-4">Built for the ecosystem</p>
                        <h2 class="font-display font-semibold text-3xl sm:text-4xl text-[var(--ink)] leading-tight mb-5">
                            Part of one sign-in, one database
                        </h2>
                        <p class="text-[var(--ink-soft)] leading-relaxed max-w-sm">
                            Dot.Tasks runs on the same ecosystem infrastructure as the rest of the Dot platforms — sign in once, and the rest follows.
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-x-10">
                        @php
                            $capabilities = [
                                ['title' => 'Ecosystem single sign-on', 'body' => 'A one-time token from the ecosystem hub logs you in and is deleted after use — no separate Dot.Tasks password to manage.'],
                                ['title' => 'Shared PostgreSQL', 'body' => 'The same database instance as the rest of the ecosystem\'s platforms, not a siloed copy.'],
                                ['title' => 'Livewire-powered board', 'body' => 'The kanban board and list view update in place, without a full page reload.'],
                                ['title' => 'Works without a live AI key', 'body' => 'No API key configured? Breakdown falls back to a fixed template instead of failing outright.'],
                                ['title' => 'Team-scoped everything', 'body' => 'Task lists and labels belong to a team, matching the shared tenancy model the rest of the ecosystem uses.'],
                                ['title' => 'An audit trail on every breakdown', 'body' => 'Prompt, response, and token count are logged for every AI decomposition call, not just the result.'],
                            ];
                        @endphp
                        @foreach ($capabilities as $c)
                            <div class="py-6 border-t border-[var(--line)] reveal" data-reveal>
                                <h3 class="font-display font-medium text-base text-[var(--ink)] mb-1.5">{{ $c['title'] }}</h3>
                                <p class="text-sm text-[var(--ink-soft)] leading-relaxed">{{ $c['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="relative py-28 sm:py-36 px-5 sm:px-8 overflow-hidden">
            <!-- Photo: two people planning at laptops with a handwritten notebook, by Scott Graham, unsplash.com photo-1454165804606-c3d57bc86b40 -->
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=2400&auto=format&fit=crop');"></div>
            <div class="absolute inset-0" style="background: linear-gradient(180deg, #faf6ec 0%, rgba(250,246,236,0.86) 50%, #faf6ec 100%);"></div>

            <div class="relative z-10 max-w-2xl mx-auto text-center reveal" data-reveal>
                <h2 class="font-display font-semibold text-3xl sm:text-4xl text-[var(--ink)] leading-tight mb-5">
                    One goal is enough to start
                </h2>
                <p class="text-[var(--ink-soft)] leading-relaxed mb-10 max-w-lg mx-auto">
                    Add a list, name a goal, and let the breakdown fill in the rest. Sign in with the same account you already use across the Dot Ecosystem.
                </p>

                @guest
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('register') }}" class="press px-8 py-3.5 bg-[var(--amber)] hover:bg-[var(--mustard)] text-[var(--ink)] font-display font-semibold rounded-lg transition-colors">
                            Create account
                        </a>
                        <a href="{{ route('login') }}" class="press px-8 py-3.5 text-[var(--ink)] font-medium rounded-lg border border-[var(--line)] hover:border-[var(--ink-soft)] transition-colors">
                            Sign in
                        </a>
                    </div>
                @endguest
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-14 px-5 sm:px-8 border-t border-[var(--line)]">
            <div class="max-w-[1400px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
                <a href="/" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-outlined.png') }}" alt="Dot.Tasks" class="h-11 w-auto opacity-90">
                </a>
                <p class="font-mono text-xs tracking-wide text-[var(--ink-soft)]">
                    &copy; {{ date('Y') }} Dot.Tasks. AI task breakdown and kanban for teams.
                </p>
            </div>
        </footer>

        <script>
            if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches && 'IntersectionObserver' in window) {
                const io = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
                document.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el));
            } else {
                document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible'));
            }
        </script>
    </body>
</html>
