<?php

namespace App\Controllers;

use App\Classes\ControllerBase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Larajax\Ui\Widgets\Filter;
use Larajax\Ui\Widgets\Form;
use Larajax\Ui\Widgets\Lists;
use Larajax\Ui\Widgets\ListStructure;
use Larajax\Ui\Widgets\Toolbar;
use ValidationException;

/**
 * Users controller.
 *
 * A resource-oriented controller that drives the Amber widgets through a
 * realistic CRUD flow:
 *
 *   index     — Toolbar + Filter + List of users
 *   structure — reorderable ListStructure of users
 *   create    — blank Form
 *   store     — onSave handler for the create form
 *   edit      — Form bound to an existing user
 *   update    — onSave handler for the edit form
 *   destroy   — onDelete handler
 *
 * The Form/List/Filter definitions live in resources/ui/user/*.yaml.
 */
class UsersController extends ControllerBase
{
    /**
     * The form widget, kept on the instance so AJAX handlers built during a
     * page action can be reached by the automatic handler routing.
     */
    public ?Form $formWidget = null;

    /**
     * Display a filtered, searchable, paginated list of users.
     */
    public function index()
    {
        $list = Lists::make(
            model: new User,
            columns: '~/resources/ui/user/columns.yaml',
            recordsPerPage: 10,
            showCheckboxes: true,
            recordUrl: 'users/:id/edit'
        );

        $filter = Filter::make(
            model: new User,
            scopes: '~/resources/ui/user/scopes.yaml',
        );

        $filter->bindToListWidget($list);

        $toolbar = Toolbar::make(
            buttons: '~/resources/ui/user/buttons.yaml',
            search: ['prompt' => 'Search users...'],
        );

        // The top bar search submits here as ?q=
        if (request()->filled('q')) {
            $toolbar->getSearchWidget()->setActiveTerm(request('q'));
        }

        $toolbar->bindToListWidget($list);

        return view('users.index', [
            'list' => $list,
            'filter' => $filter,
            'toolbar' => $toolbar,
        ]);
    }

    /**
     * Delete the checked users. AJAX handler for the list toolbar delete button.
     */
    public function onDelete()
    {
        $checkedIds = (array) input('checked');

        User::whereIn('id', $checkedIds)->get()->each->delete();

        return ajax()
            ->flash('success', 'Users deleted.')
            ->redirect('users');
    }

    /**
     * Display the users as a drag-to-reorder structure.
     */
    public function structure()
    {
        $widget = ListStructure::make(
            model: new User,
            columns: '~/resources/ui/user/columns.yaml',
            // The demo User model has no tree/parent contract, so render the
            // flat reorderable variant rather than a nested tree.
            showTree: false,
        );

        return view('users.structure', [
            'widget' => $widget,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->formWidget = $this->makeForm(new User, 'create');

        return view('users.create', [
            'formWidget' => $this->formWidget,
        ]);
    }

    /**
     * Persist a new user. AJAX handler for the create form.
     */
    public function onStore()
    {
        $this->formWidget = $this->makeForm(new User, 'create');

        $user = new User;
        $this->saveForm($user);

        return ajax()
            ->flash('success', 'User created.')
            ->redirect('users');
    }

    /**
     * Show the form for editing an existing user.
     */
    public function edit(User $user)
    {
        $this->formWidget = $this->makeForm($user, 'update');

        return view('users.edit', [
            'user' => $user,
            'formWidget' => $this->formWidget,
        ]);
    }

    /**
     * Persist changes to a user. AJAX handler for the edit form.
     */
    public function onUpdate(User $user)
    {
        $this->formWidget = $this->makeForm($user, 'update');

        $this->saveForm($user);

        return ajax()->flash('success', 'User updated.');
    }

    /**
     * Delete a user. AJAX handler for the edit form.
     */
    public function onDestroy(User $user)
    {
        $user->delete();

        return ajax()
            ->flash('success', 'User deleted.')
            ->redirect('users');
    }

    /**
     * Build the user form widget for a given context.
     */
    protected function makeForm(User $user, string $context): Form
    {
        return Form::make(
            context: $context,
            model: $user,
            fields: '~/resources/ui/user/fields.yaml',
        );
    }

    /**
     * Validate and persist form data onto the given user. Shared by the create
     * and update handlers.
     */
    protected function saveForm(User $user): void
    {
        $data = $this->formWidget->getSaveData();

        if (empty($data['name'])) {
            throw new ValidationException(['name' => 'The name field is required.']);
        }

        // Never persist a blank password (the field is left empty to keep the
        // existing one). Casts hash it on the model.
        if (empty($data['password'])) {
            unset($data['password'], $data['password_confirmation']);
        } elseif (($data['password'] ?? null) !== ($data['password_confirmation'] ?? null)) {
            throw new ValidationException(['password_confirmation' => 'The passwords do not match.']);
        }

        // The relation fields submit under their relation names: "primary_group"
        // carries the belongs-to key, "groups" the many-to-many ids. Map the
        // former onto its foreign key and defer the latter to a sync() after
        // the record exists.
        if (array_key_exists('primary_group', $data)) {
            $data['primary_group_id'] = $data['primary_group'] ?: null;
        }

        // An empty selection submits an empty string, which clears the groups
        $hasSecondaryGroups = array_key_exists('groups', $data);
        $secondaryGroups = ($data['groups'] ?? null) ?: [];
        unset($data['groups'], $data['primary_group'], $data['password_confirmation'], $data['send_invite']);

        $user->fill($data)->save();

        if ($hasSecondaryGroups) {
            $user->groups()->sync($secondaryGroups);
        }
    }
}
