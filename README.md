# CpE Student Development Portfolio and PLO Attainment System

A Laravel 12 application for the BS Computer Engineering program: students build one
portfolio per academic year, faculty validate the evidence, and the program chair sees
whether each Program Learning Outcome is actually being attained.

Built around a single rule: **the portfolio closes before the academic year ends**, and
everything in the system — deadlines, provisioning, attainment snapshots, exports — hangs
off that yearly cycle.

---

## Requirements

| Needs | Version |
| --- | --- |
| PHP | 8.2 or newer |
| MySQL / MariaDB | 8.0 / 10.6 or newer |
| Composer | 2.x |
| Node | 18 or newer |

XAMPP works fine: start Apache and MySQL, then run the app with `php artisan serve`
rather than pointing a vhost at `public/` (simpler, and Vite's dev server expects it).

## Install

```bash
composer install
npm install
cp .env.example .env
```

Create the database, then set `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.

```bash
composer setup        # key:generate + migrate:fresh --seed + storage:link
npm run dev           # in a second terminal
php artisan serve
```

Open <http://localhost:8000>.

### Demo accounts

Every seeded account uses the password `password`.

| Role | Email | Sees |
| --- | --- | --- |
| Student | `student@usl.edu.ph` | A fourth-year portfolio filled in end to end |
| Faculty | `villanueva@usl.edu.ph` | Review queue, evidence rating, assessment form |
| Chair | `chair@usl.edu.ph` | Cohort attainment dashboard and the CQI loop |
| Admin | `admin@usl.edu.ph` | Academic calendar and deadline schedules |

Six other students are seeded across years 1–4 with empty portfolios, so the cohort
dashboard shows the realistic mix of complete and under-assessed outcomes.

**Delete `database/seeders/DemoDataSeeder.php` before using this with real records.**

---

## How the assessment actually works

This is the part that matters for accreditation, so it is worth being precise.

```
PLO attainment = weighted mean of VALID DIRECT evidence
```

Evidence is valid only when all three hold:

1. a faculty evaluator awarded the level (not the student's own claim),
2. the evaluator validated it, and
3. the supporting file was rated **Adequate** or **Strong** on the Evidence Quality Scale.

Weights live in `config/portfolio.php`:

| Source | Weight |
| --- | --- |
| Course or laboratory output | 1.0 |
| Project, design project, research | 1.5 |
| OJT, capstone | 2.0 |

Capstone and OJT count double because they demonstrate integrated competence rather than
an isolated skill.

**Student self-assessment is indirect evidence.** So are exit surveys, employer feedback
and alumni feedback. They are computed, stored and displayed — in their own column, beside
the direct figure, never averaged into it. A PLO with fewer than two valid direct records
is reported as *under-assessed* rather than passed or failed, because an average of one
data point is not an average.

All of the above is configuration, not code: change `PLO_ATTAINMENT_TARGET` or
`PLO_MIN_EVIDENCE_QUALITY` in `.env` and every dashboard, flag and export follows.

---

## The twelve sections

`config/portfolio.php` is the single source of truth for the portfolio's shape — which
sections exist, which year levels they apply to, and how each is edited.

| # | Section | Years | Editor |
| --- | --- | --- | --- |
| 1 | Student Profile | 1–4 | config fields |
| 2 | Personal Development Plan | 1–4 | config fields |
| 3 | PLO and Competency Matrix | 1–4 | `PloMatrix` |
| 4 | Course-Based Evidence | 1–4 | `CourseEvidence` |
| 5 | Technical Competency Portfolio | 1–4 | `TechnicalCompetency` |
| 6 | Engineering Design Portfolio | 2–4 | `DesignProject` |
| 7 | Research and Investigation | 3–4 | config fields |
| 8 | Professional Competency | 1–4 | config fields |
| 9 | Industry / OJT Portfolio | 4 | `OjtRecordForm` |
| 10 | Capstone Project Portfolio | 4 | `CapstoneForm` |
| 11 | Professional Development | 1–4 | `ProfessionalDevelopment` |
| 12 | Student Reflection | 1–4 | `ReflectionForm` |

Sections marked *config fields* are rendered by one generic Livewire component
(`App\Livewire\Portfolio\SectionForm`) straight from the `fields` array. Adding or
rewording a question there needs no migration, no new class and no new view — which is the
point, because the question set changes between accreditation cycles.

### Adding a question

```php
// config/portfolio.php, section 2
['name' => 'mentor', 'label' => 'Who is mentoring you this year?', 'type' => 'text', 'required' => false],
```

That is the whole change. Answers live in `section_entries.payload` as JSON. Anything that
has to be *queried* — scores, levels, PLO links — gets a real column in a real table
instead.

---

## Curriculum mapping

`Program → Curriculum mapping` (chair or admin) is where faculty maintain course learning
outcomes and map them to PLOs, with a target level per mapping so a first-year course can
claim to *introduce* an outcome without claiming to develop it to proficiency.

The screen opens with a coverage strip across all 14 outcomes. Anything mapped by zero
courses shows red — an outcome nothing claims to teach cannot be attained, and that is a
curriculum problem rather than a student one. The PLO drill-down links straight here when
it finds a gap.

Two guards worth knowing:

- a CLO mapped to no PLO is rejected, because it would look like coverage while producing
  no attainment data;
- a CLO already cited by a student's course evidence cannot be deleted, only reworded, so
  submitted evidence is never orphaned.

## Deadlines

Each year level gets a published schedule: semester checkpoints for the sections that
benefit from being spread out, and one deadline before the academic year ends for the
portfolio as a whole.

Late work is **flagged, not blocked**, within a grace window (7 days by default), because a
late portfolio is better evidence than a missing one. Set `locks_editing` on a deadline to
hard-close it instead. Per-student extensions are supported and override both.

```bash
php artisan portfolio:open-year 2027-2028   # marks current, creates portfolios, builds schedules
php artisan portfolio:process-deadlines     # reminders at 14/7/1 days, flags overdue
php artisan portfolio:recompute-attainment  # refresh snapshots
```

Point cron at `php artisan schedule:run` to run the first two automatically.

---

## The OJT supervisor link

Industry supervisors do not get accounts. The student generates a single-use tokenised
link in Section 9 and emails it to their supervisor, who fills an eight-item rating form
and never sees anything else in the system. Authentic industry evaluation, no portal
registration for a busy engineer.

---

## Architecture

```
app/
  Support/Enums/        Role, SubmissionStatus, AttainmentLevel, EvidenceQuality,
                        CompetencyStage, AssessmentType, DeadlineScope
  Models/               36 Eloquent models
  Services/             the brain — all business rules live here, not in controllers
    PloAttainmentService        the weighted-mean computation and its flags
    PortfolioProvisioningService  creates the yearly shell, idempotently
    PortfolioCompletionService    per-section completion maths
    DeadlineService               due dates, grace, extensions, edit locks
    CqiService                    drafts improvement actions from live data
    PortfolioExportService        the Word document
  Livewire/             ten section editors plus the faculty assessment form
  Http/Controllers/     thin — routing, authorization, delegation
  Policies/             PortfolioPolicy decides who may view, edit and evaluate
config/portfolio.php    sections, assessment policy, rubrics, prompts, criteria
database/migrations/    16 migrations
database/seeders/       PLOs, competencies, USL BSCPE 2023 curriculum, calendar, demo data
```

Evidence files are stored on a private disk (`storage/app/private/evidence`) and streamed
through `EvidenceController` after a policy check. Nothing is web-reachable by path.

---

## Exports

| Output | Route | Built by |
| --- | --- | --- |
| Accomplished portfolio (Word) | `/portfolios/{id}/export/word` | PhpWord, `PortfolioExportService` |
| Accomplished portfolio (PDF) | `/portfolios/{id}/export/pdf` | DomPDF, `resources/views/exports/portfolio.blade.php` |
| Competency transcript (PDF) | `/students/{id}/transcript` | DomPDF, `exports/transcript.blade.php` |

Both portfolio exports reproduce the department's printed form section by section: the
cover block, Sections 1–12 in order, the Faculty Assessment Form with ticks in the rated
column, the PLO Attainment Dashboard, and the Student Competency Transcript — same column
headings, same legends, same footnotes.

They also reproduce its **two level-naming schemes**, which is easy to mistake for a bug:

- the **matrix and the course-evidence Level column** read I / D / A / P — Introduced,
  Developing, **Applied**, Proficient;
- the **attainment dashboard** reports the numeric scale — 1 Introduced, 2 Developing,
  **3 Proficient**, 4 Advanced.

So level 3 prints as "Applied" in one table and "Proficient" in another, exactly as the
paper form does. `AttainmentLevel::matrixLabel()` and `AttainmentLevel::label()` are the
two sides of that.

Both exports read their Year 1–4 grids from `PortfolioMatrixService`, so the Word file and
the PDF cannot disagree about a student's attainment. Years the student has not reached
print as "Pending"; years with no evidence print as an em dash.

---

## Seeded curriculum

The full USL BS Computer Engineering checklist (curriculum version 2023) is seeded from
the official program checklist: 74 courses across four years plus two summers, with the
240-hour OJT in the summer after third year. Unit counts are derived from the fourth digit
of the course number, which is how the coding scheme already encodes them.

Each major course carries a starter CLO mapped to its PLOs, so the chair's "where is this
outcome taught?" view works on first run. Faculty replace those with the CLOs from their
own syllabi.

---

## Known gaps

Deliberate, so you know what you are inheriting:

- No password reset flow. Accounts are issued by the department office; add Breeze's
  reset controllers if that changes.
- No automated tests beyond a smoke test. The attainment service is the thing worth
  testing first if you extend this.
- Faculty maintain CLOs through the chair's curriculum screen; there is no per-instructor
  scoping yet, so any chair or admin can edit any course's outcomes.
- Evidence files are stored locally. Move the `evidence` disk to S3 in `config/filesystems.php`
  if the department wants off-server backups.
- The Word export renders tables but not embedded images; the PDF export is the better
  one to print.
