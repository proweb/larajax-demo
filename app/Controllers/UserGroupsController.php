<?php

namespace App\Controllers;

use App\Classes\ControllerBase;
use App\Models\UserGroup;
use Larajax\Ui\Widgets\Form;
use Larajax\Ui\Widgets\Lists;
use Larajax\Ui\Widgets\Toolbar;
use ValidationException;

/**
 * User groups controller.
 *
 * A second, deliberately simpler resource — a plain list and form with no
 * filter — showing that the widgets are driven entirely by configuration and
 * are not specific to the User model.
 */
class UserGroupsController extends ControllerBase
{
    public ?Form $formWidget = null;

    public ?Lists $listWidget = null;

    /**
     * Display a list of user groups. Clicking a row edits the group in a popup.
     */
    public function index()
    {
        $list = $this->listWidget = Lists::make(
            model: new UserGroup,
            columns: '~/resources/ui/user_group/columns.yaml',
            recordsPerPage: 10,
            recordOnClick: 'popup',
        );

        $toolbar = Toolbar::make(
            buttons: '~/resources/views/user_groups/_list_toolbar.php',
            search: ['prompt' => 'Search groups...'],
        );

        $toolbar->bindToListWidget($list);

        return view('user_groups.index', [
            'list' => $list,
            'toolbar' => $toolbar,
        ]);
    }

    /**
     * Render a group's form in a popup. AJAX handler for the list's "popup" record click.
     */
    public function onLoadPopupForm()
    {
        $group = UserGroup::findOrFail(input('form_record_id'));

        $this->formWidget = $this->makeForm($group, 'update');

        return view('user_groups._popup_form', [
            'group' => $group,
            'formWidget' => $this->formWidget,
        ])->render();
    }

    /**
     * Save a group from the popup form and refresh the list.
     */
    public function onSavePopupForm()
    {
        $group = UserGroup::findOrFail(input('form_record_id'));

        $this->formWidget = $this->makeForm($group, 'update');

        $this->saveForm($group);

        return ajax()
            ->flash('success', 'Group updated.')
            ->update($this->listWidget->onRefresh());
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        $this->formWidget = $this->makeForm(new UserGroup, 'create');

        return view('user_groups.create', [
            'formWidget' => $this->formWidget,
        ]);
    }

    /**
     * Persist a new group. AJAX handler for the create form.
     */
    public function onStore()
    {
        $this->formWidget = $this->makeForm(new UserGroup, 'create');

        $this->saveForm(new UserGroup);

        return ajax()
            ->flash('success', 'Group created.')
            ->redirect('user-groups');
    }

    /**
     * Show the form for editing an existing group.
     */
    public function edit(UserGroup $userGroup)
    {
        $this->formWidget = $this->makeForm($userGroup, 'update');

        return view('user_groups.edit', [
            'userGroup' => $userGroup,
            'formWidget' => $this->formWidget,
        ]);
    }

    /**
     * Persist changes to a group. AJAX handler for the edit form.
     */
    public function onUpdate(UserGroup $userGroup)
    {
        $this->formWidget = $this->makeForm($userGroup, 'update');

        $this->saveForm($userGroup);

        return ajax()->flash('success', 'Group updated.');
    }

    /**
     * Delete a group. AJAX handler for the edit form.
     */
    public function onDestroy(UserGroup $userGroup)
    {
        $userGroup->delete();

        return ajax()
            ->flash('success', 'Group deleted.')
            ->redirect('user-groups');
    }

    /**
     * Build the group form widget for a given context.
     */
    protected function makeForm(UserGroup $group, string $context): Form
    {
        return Form::make(
            context: $context,
            model: $group,
            fields: '~/resources/ui/user_group/fields.yaml',
        );
    }

    /**
     * Validate and persist form data onto the given group.
     */
    protected function saveForm(UserGroup $group): void
    {
        $data = $this->formWidget->getSaveData();

        if (empty($data['name'])) {
            throw new ValidationException(['name' => 'The name field is required.']);
        }

        // The members field submits related user ids, synced once the group exists
        $hasMembers = array_key_exists('users', $data);
        $members = ($data['users'] ?? null) ?: [];
        unset($data['users']);

        // The session key commits the file changes the upload fields deferred, such as a removed document
        $group->fill($data)->save(null, $this->formWidget->getSessionKey());

        if ($hasMembers) {
            $group->users()->sync($members);
        }
    }
}
