<?php

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;
use App\Support\Enums\Role;

/**
 * Who may see and change a portfolio.
 *
 *   student - only their own, and only while it is editable
 *   faculty - any portfolio they advise or have been asked to evaluate
 *   chair / admin - everything (handled by the Gate::before in AppServiceProvider)
 */
class PortfolioPolicy
{
    public function view(User $user, Portfolio $portfolio): bool
    {
        if ($user->isRole(Role::Student)) {
            return $user->student?->id === $portfolio->student_id;
        }

        return $user->isRole(Role::Faculty);
    }

    /** Editing the content is the student's job alone. */
    public function update(User $user, Portfolio $portfolio): bool
    {
        return $user->isRole(Role::Student)
            && $user->student?->id === $portfolio->student_id
            && $portfolio->isEditableByStudent();
    }

    public function submit(User $user, Portfolio $portfolio): bool
    {
        return $this->update($user, $portfolio);
    }

    /** Validating levels and rating evidence is the evaluator's job alone. */
    public function evaluate(User $user, Portfolio $portfolio): bool
    {
        return $user->isRole(Role::Faculty);
    }

    public function export(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }
}
