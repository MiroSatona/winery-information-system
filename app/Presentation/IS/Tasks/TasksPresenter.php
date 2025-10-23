<?php

declare(strict_types=1);

namespace App\Presentation\IS\Tasks;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Task\TaskType;
use App\Model\Task\Task;
use App\Model\Task\TaskState;
use App\Model\Task\TaskData;
use App\Model\User\UserRepository;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\Button;
use Nette\Utils\DateTime;

final class TasksPresenter extends BasePresenter
{

    public function __construct(private UserRepository $userRepository)
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(Role::WORKER->name) || $this->user->isInRole(Role::VINTNER->name))) {
            $this->redirect(':Home:default');
        }
    }

    public function renderDashboard(): void
    {

    }
    
    public function renderAddTask1(): void 
    {

    }

    public function createComponentAddTaskForm(): Form
    {
       $form = new Form() ;
       
       $form->addText("name", "Název úkonu")
       ->setHtmlAttribute("class", "form-control form-control-sm")
       ->setHtmlAttribute("placeholder", "zadejte název úkolu ...");
   
        $form->addSelect('type', 'typ', [
           TaskType::BOTTLING->value => "Lahvování",
           TaskType::HARVEST->value => "Sklizeň",
           TaskType::TREATMENT->value => "Ošetření"
        ])
        ->setHtmlAttribute("class", "form-select from-select-sm")
        ->setDefaultValue(TaskType::BOTTLING->value)
        ->setHtmlAttribute("style", "width: 150px;");
       
        $form->addTextArea("note", "Poznámka")
         ->setRequired('Zadejte popis vinohradu.')
        ->setHtmlAttribute('class', 'form-control')
        ->setHtmlAttribute('rows', 4)
        ->setHtmlAttribute('placeholder', 'Zadejte popis vinohradu');
    
        $form->addText('planned_date', 'Datum provedení')
        ->setHtmlType('date') // 👈 makes it an <input type="date">
        ->setRequired('Zadejte plánované datum provedení.')
        ->setHtmlAttribute('class', 'form-control form-control-sm')
        ->setHtmlAttribute('style', 'width: 200px;')
        ->addRule(Form::Pattern, 'Datum musí být ve formátu RRRR-MM-DD', '\d{4}-\d{2}-\d{2}');

        $form->addSubmit('send', 'Uložit a pokračovat')
            ->setHtmlAttribute('class', 'btn btn-primary btn-sm');
        
        $form->onSuccess[] = [$this, 'addTaskFromSucceeded'];
        
        return $form;
    }
    
    public function createComponentTaskWorkersForm(): Form
    {
        $form = new Form();

        // fetch active workers
        $workers = $this->userRepository->fetchByAdmin(Role::WORKER->value, '', 'active');
        $newTask = $this->fetchCurrentNewTask();
        $selected = $newTask->getIdsOfWorkers() ?? [];

        // Build options excluding already-selected workers so they disappear from the dropdown
        $options = [];
        foreach ($workers as $w) {
            if (in_array($w->getId(), $selected, true)) {
                continue; // skip already selected
            }
            $options[$w->getId()] = $w->getFullName() . ' (' . $w->getUsername() . ')';
        }

        // Use a select to add a single worker at once (server-driven flow)
        $form->addSelect('worker', 'Pracovník', ['' => 'Vyberte pracovníka ...'] + $options)
            ->setHtmlAttribute('class', 'form-select')
            ->setDefaultValue('');

        $form->addSubmit('backToDashboard', 'Zrušit')
            ->setHtmlAttribute('class', 'btn btn-secondary')
            ->onClick[] = [$this, 'backToDashboardTaskCreation'];

        $form->addSubmit('addWorker', 'Přidat')
            ->setHtmlAttribute('class', 'btn btn-primary mt-2')
            ->onClick[] = [$this, 'workerSelectionFormSucceeded'];

        $form->addSubmit('clearWorkers', 'Vymazat výběr')
            ->setHtmlAttribute('class', 'btn btn-outline-danger mt-2')
            ->onClick[] = [$this, 'clearWorkers'];

        return $form;
    }
    
    private function fetchCurrentNewTask() {
        $section = $this->session->getSection("TaskCreation");

        $newTask = $section->get("newTask");
        
        if ($newTask == null) {
            $newTask = new TaskData();
            $section->set("newTask", $newTask);
        }       
        return $newTask;
    }
    
    private function finalizeCurrentTask() {
        
    }

    public function addTaskFromSucceeded(Form $form, \stdClass $values): void 
    {
        $newTask = $this->fetchCurrentNewTask();

            // set basic fields from form values using TaskData setters
            if (isset($values->name)) {
                $newTask->setName((string) $values->name);
            }

            if (isset($values->note)) {
                $newTask->setNote((string) $values->note);
            }

            // planned_date is provided as YYYY-MM-DD (or empty)
            $planned = null;
            if (isset($values->planned_date) && $values->planned_date !== '') {
                try {
                    $planned = new DateTime((string) $values->planned_date);
                } catch (\Exception $e) {
                    // keep null on parse failure
                    $planned = null;
                }
            }
            $newTask->setPlannedTimestamp($planned);
            
            if (isset($values->type)) {
                $newTask->setType($values->type);
            }

            // persist updated TaskData back into session
            $section = $this->session->getSection("TaskCreation");
            $section->set("newTask", $newTask);

            // proceed to next step or reload add task page
            $this->redirect('selectWorkers');
    }
    
    public function backToDashboardTaskCreation(Button $button, \stdClass $values): void
    {
        // clear task creation session and go back to dashboard
        $this->session->getSection('TaskCreation')->set('newTask', null);
        $this->redirect('dashboard');
    }

    public function taskWorkersFormSucceeded(Button $button, \stdClass $values): void
    {
        $newTask = $this->fetchCurrentNewTask();
        // This handler is kept for backward compatibility but not used by the new server-driven flow
        $this->redirect('this');
    }

    public function clearWorkers(Button $button, \stdClass $values): void
    {
        $newTask = $this->fetchCurrentNewTask();
        $newTask->setIdsOfWorkers([]);
        $this->session->getSection('TaskCreation')->set('newTask', $newTask);

        $this->flashMessage('Výběr pracovníků byl vymazán.', 'success');
        $this->redirect('this');
    }

    public function workerSelectionFormSucceeded(Button $button, \stdClass $values): void
    {
        // add single selected worker to session TaskData
        if (!isset($values->worker) || $values->worker === '') {
            $this->flashMessage('Nevybrali jste pracovníka.', 'warning');
            $this->redirect('this');
            return;
        }

        $id = (int) $values->worker;
        $newTask = $this->fetchCurrentNewTask();
        $ids = $newTask->getIdsOfWorkers() ?? [];
        if (!in_array($id, $ids, true)) {
            $ids[] = $id;
            $newTask->setIdsOfWorkers($ids);
            $this->session->getSection('TaskCreation')->set('newTask', $newTask);
            $this->flashMessage('Pracovník přidán.', 'success');
        } else {
            $this->flashMessage('Tento pracovník je již přidán.', 'warning');
        }
        $this->redirect('this');
    }

    public function handleRemoveWorkerFromSelection(int $workerId): void
    {
        $newTask = $this->fetchCurrentNewTask();
        $ids = $newTask->getIdsOfWorkers() ?? [];
        $updated = array_values(array_filter($ids, fn($i) => $i !== $workerId));
        $newTask->setIdsOfWorkers($updated);
        $this->session->getSection('TaskCreation')->set('newTask', $newTask);
        $this->flashMessage('Pracovník odebrán.', 'success');
        $this->redirect('this');
    }
    
    public function renderSelectWorkers(): void
    {
        $newTask = $this->fetchCurrentNewTask();
        $ids = $newTask->getIdsOfWorkers() ?? [];

        // fetch all active workers
        $allWorkers = $this->userRepository->fetchByAdmin(Role::WORKER->value, '', 'active');

        $selected = [];
        $available = [];

        foreach ($allWorkers as $worker) {
            if (in_array($worker->getId(), $ids, true)) {
                $selected[] = $worker;
            } else {
                $available[] = $worker;
            }
        }

        $this->template->selectedWorkers = $selected;
        $this->template->availableWorkers = $available;
    }
    
    public function rendertmpTaskPart3()
    {
       bdump($this->fetchCurrentNewTask());
    }
    
}
