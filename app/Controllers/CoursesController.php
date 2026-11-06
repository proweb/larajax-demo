<?php

namespace App\Controllers;

use App\Classes\ControllerBase;
use App\Models\Course;
use Larajax\Ui\Widgets\Form;
use Larajax\Ui\Widgets\Lists;
use Larajax\Ui\Widgets\ListStructure;
use Larajax\Ui\Widgets\Toolbar;

class CoursesController extends ControllerBase
{
    public ?Form $formWidget = null;

    public function index()
    {
        $list = Lists::make(
            model: new Course,
            columns: '~/resources/ui/course/columns.yaml',
            recordsPerPage: 20,
            showCheckboxes: true,
            recordUrl: 'courses/:id/edit'
        );

        $toolbar = Toolbar::make(
            buttons: '~/resources/ui/course/buttons.yaml',
            search: ['prompt' => 'Search courses...'],
        );

        if (request()->filled('q')) {
            $toolbar->getSearchWidget()->setActiveTerm(request('q'));
        }

        $toolbar->bindToListWidget($list);

        return view('courses.index', [
            'list' => $list,
            'toolbar' => $toolbar,
        ]);
    }

    public function onDelete()
    {
        foreach ((array) request('checked') as $id) {
            $course = Course::find((int) $id);
            if ($course) {
                $course->delete();
            }
        }

        return ajax()
            ->flash('success', 'Courses deleted.')
            ->redirect('courses');
    }

    public function structure()
    {
        $widget = ListStructure::make(
            model: new Course,
            columns: '~/resources/ui/course/columns.yaml',
            showTree: false,
        );

        return view('courses.structure', [
            'widget' => $widget,
        ]);
    }

    public function create()
    {
        $this->formWidget = $this->makeForm(new Course, 'create');

        return view('courses.create', [
            'formWidget' => $this->formWidget,
        ]);
    }

    public function onStore()
    {
        $this->formWidget = $this->makeForm(new Course, 'create');

        $data = $this->formWidget->getSaveData();

        if (empty($data['title'])) {
            abort(422, 'Title is required.');
        }

        if (!empty($data['price'])) {
            $data['price'] = (float) $data['price'];
        } else {
            $data['price'] = 0;
        }

        $course = Course::create($data);

        return ajax()
            ->flash('success', 'Course created.')
            ->redirect('courses');
    }

    public function edit(Course $course)
    {
        $this->formWidget = $this->makeForm($course, 'update');

        return view('courses.edit', [
            'course' => $course,
            'formWidget' => $this->formWidget,
        ]);
    }

    public function onUpdate(Course $course)
    {
        $this->formWidget = $this->makeForm($course, 'update');

        $data = $this->formWidget->getSaveData();

        if (empty($data['title'])) {
            abort(422, 'Title is required.');
        }

        if (!empty($data['price'])) {
            $data['price'] = (float) $data['price'];
        }

        $course->fill($data)->save();

        return ajax()
            ->flash('success', 'Course updated.');
    }

    public function onDestroy(Course $course)
    {
        $course->delete();

        return ajax()
            ->flash('success', 'Course deleted.')
            ->redirect('courses');
    }

    protected function makeForm(Course $course, string $context): Form
    {
        return Form::make(
            context: $context,
            model: $course,
            fields: '~/resources/ui/course/fields.yaml',
        );
    }
}
