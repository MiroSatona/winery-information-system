<?php

declare(strict_types=1);

namespace App\Presentation\IS\Tasks;

use App\Model\FlashMsgType;
use Nette\Application\UI\Form;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Task\TaskType;
use App\Model\Task\TaskData;
use App\Model\Task\Task;
use App\Model\Task\Treatment\Treatment;
use App\Model\Task\Harvest\Harvest;
use App\Model\Task\Bottling\Bottling;
use App\Model\Task\TaskRepository;
use App\Model\User\UserRepository;
use App\Model\Task\TaskState;
use App\Model\Task\Harvest\HarvestRepository;  
use App\Model\Validation\ValidationDefinitions;
use App\Model\Wine\WineRepository;
use App\Model\Vineyard\VineyardRepository;
use App\Model\Image\ImageRepository;
use App\Model\Task\Bottling\BottlingRepository;
use App\Model\Image\Image;
use App\Model\Task\Treatment\TreatmentType;
use App\Model\WineVariety\WineVarietyRepository;
use Nette\Forms\Controls\Button;
use Nette\Utils\DateTime;
use Tracy\Debugger;
use Tracy\ILogger;

final class TasksPresenter extends BasePresenter
{
    public function __construct(
        private UserRepository $userRepository,
        private TaskRepository $taskRepository,
        private BottlingRepository $bottlingRepository,
        private HarvestRepository $harvestRepository,
        private WineRepository $wineRep,
        private ImageRepository $imgRep,
        private WineVarietyRepository $wineVarietyRep,
        private VineyardRepository $vineyardRep
    ) {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(Role::WORKER->name) || $this->user->isInRole(Role::VINTNER->name))) {
            $this->redirect(':Home:default');
        }

        $this->template->TaskStateType = TaskState::class;
        $this->template->HarvestType = Harvest::class;
        $this->template->TreatmentType = Treatment::class;
        $this->template->BottlingType = Bottling::class;
    }

    public function renderDashboard(): void
    {
        $pattern = $this->getParameter('pattern') ?? '';
        $type = $this->getParameter('type') ?? '';
        $state = $this->getParameter('state') ?? '';
        
        $this->template->tasks = $this->taskRepository->fetchTasks($pattern, $type, $state);
        $this->template->roleType = TaskState::class;
    }
    
    public function renderAddTask1(): void 
    {
        $form = $this->getComponent("addTaskForm");
        $newTask = $this->fetchCurrentNewTask();
        
        $form['name']->setValue($newTask->getName());
        $form["note"]->setValue($newTask->getNote());
        $form["type"]->setValue($newTask->getType());
        if ($newTask->getPlannedTimestamp()) {
            $form["planned_date"]->setValue($newTask->getPlannedTimestamp());
        }
    }

    public function createComponentAddTaskForm(): Form
    {
        $form = new Form();

        $form->addText("name", "Název úkonu")
            ->addRule(Form::MinLength, 'Název úkolu musí mít alespoň %d znaků.', ValidationDefinitions::TASK_NAME_LENGTH_MIN)
            ->addRule(Form::MaxLength, 'Název úkolu může mít maximálně %d znaků.', ValidationDefinitions::TASK_NAME_LENGTH_MAX)
            ->setRequired('Zadejte název úkolu.')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", "Zadejte název úkolu ...");
   
        $form->addSelect('type', 'Typ úkonu', [
            TaskType::BOTTLING->value => "Lahvování",
            TaskType::HARVEST->value => "Sklizeň",
            TaskType::TREATMENT->value => "Ošetření"
        ])
            ->setRequired('Zvolte typ úkonu.')
            ->setHtmlAttribute("class", "form-select")
            ->setDefaultValue(TaskType::BOTTLING->value)
            ->setHtmlAttribute("style", "width: 150px;");
       
        $form->addTextArea("note", "Poznámka")
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte poznámku');
    
        $form->addDateTime('planned_date', 'Datum plánovaného provedení')
            ->setValue(new DateTime('now'))
            ->setRequired('Zadejte plánované datum provedení.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('style', 'width: 200px;');
        
        $send = $form->addSubmit('send', 'Pokračovat');
        $send->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');
        $send->getControlPrototype();

        // handle form success
        $form->onSuccess[] = [$this, 'addTaskFromSucceeded'];

        return $form;
    }
    
    public function handleClearForm(): void
    {
        $section = $this->session->getSection("TaskCreation");
        $section->set("newTask", null);
        $this->redirect('dashboard');
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
            ->setHtmlAttribute('class', 'btn btn-danger w-100 fw-bold')
            ->onClick[] = [$this, 'handleClearForm'];

        $form->addSubmit('addWorker', 'Přidat')
            ->setHtmlAttribute('class', 'btn btn-primary mt-2')
            ->onClick[] = [$this, 'workerSelectionFormSucceeded'];
        
        $form->addSubmit('goBack', "Zpět")
            ->setHtmlAttribute('class', 'btn btn-secondary w-100 fw-bold')
            ->onClick[] = [$this, 'goBackTo1'];

        $form->addSubmit('clearWorkers', 'Vymazat výběr')
            ->setHtmlAttribute('class', 'btn btn-outline-danger mt-2')
            ->onClick[] = [$this, 'clearWorkers'];
        
        $form->addSubmit("next", "Pokračovat")
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold')
            ->onClick[] = [$this, "goToNextForm"];

        return $form;
    }
    
    public function goBackTo1() {
        $this->redirect("addTask1");
    }
    
    public function goToNextForm() 
    {
        $newTask = $this->fetchCurrentNewTask();
        
        if (!$newTask->getIdsOfWorkers()) {
            $this->flashMessage("Úkon musí být někomu přiřazen!", FlashMsgType::ERROR->value);
            $this->redirect("this");
            return;
        }

        switch($newTask->getType()) {
        case TaskType::TREATMENT: 
            $this->redirect("treatment");
            break;
        case TaskType::HARVEST:
            $this->redirect("harvest");
            break;
        case TaskType::BOTTLING:
            $this->redirect("bottling");
            break;
        default:
            bdump($newTask->getType());
            break;
        }
    }

    private function fetchCurrentNewTask(): TaskData {
        $section = $this->session->getSection("TaskCreation");

        $newTask = $section->get("newTask");
        
        if ($newTask == null) {
            $newTask = new TaskData();
            $section->set("newTask", $newTask);
        }       
        return $newTask;
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

        $planned = null;
        if (isset($values->planned_date) && $values->planned_date !== '') {
            try {
                $planned = DateTime::from($values->planned_date);
            } catch (\Exception $e) {
                // keep null on parse failure
                $planned = null;
            }
        }
        $newTask->setPlannedTimestamp($planned);
        
        if (isset($values->type)) {
            $newTask->setType(TaskType::from((string) $values->type));
        }

        // persist updated TaskData back into session
        $section = $this->session->getSection("TaskCreation");
        $section->set("newTask", $newTask);

        // proceed to next step or reload add task page
        $this->redirect('selectWorkers');
    }
    
    public function backToDashboardTaskCreation(Button $button, \stdClass $values): void
    {
        $this->redirect('addTask1');
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

        $this->flashMessage('Výběr pracovníků byl vymazán.', FlashMsgType::SUCCESS->value);
        $this->redirect('this');
    }

    public function workerSelectionFormSucceeded(Button $button, \stdClass $values): void
    {
        // add single selected worker to session TaskData
        if (!isset($values->worker) || $values->worker === '') {
            $this->flashMessage('Nevybrali jste pracovníka.', FlashMsgType::ERROR->value);
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
            $this->flashMessage('Pracovník přidán.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Tento pracovník je již přidán.', FlashMsgType::ERROR->value);
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
        $this->flashMessage('Pracovník odebrán.', FlashMsgType::SUCCESS->value);
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
    
    /**
     * createComponentFilterForm
     * Method for creating the filter form in the dashboard of tasks.
     * 
     * @return Form
     */
    protected function createComponentFilterForm(): Form
    {
        /** @var Form  $form Form for filtering users */
        $form = new Form;

        // Set method get for getting parameters for filtering
        $form->getElementPrototype()->method = 'get';
        // Set class for form layout
        $form->getElementPrototype()->class = 'd-flex flex-row gap-3 justify-content-center align-items-center flex-grow-1 mx-4';

        // Set input for searching users by typing
        $form->addText('pattern', '')
            ->setHtmlAttribute('type', 'search')
            ->setHtmlAttribute('placeholder', 'Hledat úkony')
            ->setHtmlAttribute('class', 'form-control flex-grow-1 form-control-sm')
            ->setHtmlAttribute('style', 'max-width: 300px;');

        // Set selection bar for filtering by state
        $form->addSelect('state', '', [
            '' => 'Všechny stavy',
            TaskState::PLANNED->value => 'Plánované',
            TaskState::CANCELED->value => 'Zrušené',
            TaskState::FINISHED->value => 'Dokončené',
        ])
        ->setHtmlAttribute('class', 'form-select form-select-sm')
        ->setHtmlAttribute('style', 'width: 100px;');

        // Set selection bar for filtering by type
        $form->addSelect('type', '', [
            '' => 'Všechny typy',
            TaskType::BOTTLING->value => 'Lahvování',
            TaskType::HARVEST->value => 'Sklizeň',
            TaskType::TREATMENT->value => 'Ošetření'
        ])

        ->setHtmlAttribute('class', 'form-select form-select-sm')
        ->setHtmlAttribute('style', 'width: 100px;');

        // Button for submitting the form
        $form->addSubmit('send', 'Hledat')
            ->setHtmlAttribute('class', 'btn btn-primary btn-sm');
        
        // Config form rendering
        $formRender = $form->getRenderer();
        $formRender->wrappers['controls']['container'] = null;
        $formRender->wrappers['pair']['container'] = null;
        $formRender->wrappers['label']['container'] = null;
        $formRender->wrappers['control']['container'] = null;

        // Call the method filterForm on form success
        $form->onSuccess[] = [$this, 'filterForm'];
        $form->onError[] = [$this, 'formError'];

        return $form;
        
    }

    /**
     * filterForm
     * Method for handling the filter form submission.
     * @param \stdClass $values Filter values.
     * @return void
     */
    public function filterForm(\stdClass $values): void
    {   
        /** @var string $pattern Pattern for filtering tasks */
        $pattern = $values->pattern ;
        /** @var string $state State for filtering tasks */
        $state = $values->state;
        /** @var string $type Type for filtering tasks */
        $type = $values->type;
        /** @var Task[] $tasks Filtered tasks */
        $tasks = $this->taskRepository->fetchTasks($pattern, $type, $state);

        // Set filtered tasks to template
        $this->template->tasks = $tasks;

    }

    /**
     * handleDetails
     * Method for handling program flow after task details button is pressed.
     * 
     * @param int $taskId ID of the task to show details for.
     * @return void
     */
    public function handleDetails(int $taskId): void
    {
        /** @var Task $task Task to show details for. */
        $task = $this->taskRepository->fetchById($taskId);

        if ($task === null) {
            $this->flashMessage('Chyby při načítání úkonu z databáze, zkuste to prosím znovu.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
            return;
        }

        if ($task instanceof Harvest) {
            $this->redirect('detailHarvest', ['id' => $taskId]);
            return;
        }

        if ($task instanceof Bottling) {
            $this->redirect('detailBottling', ['id' => $taskId]);
            return;
        }

        if ($task instanceof Treatment) {
            $this->redirect('detailTreatment', ['id' => $taskId]);
            return;
        }

        $this->flashMessage('Chyba při načítání úkonu z databáze, zkuste to prosím znovu.', FlashMsgType::ERROR->value);
        $this->redirect('dashboard');


    }

    /**
     * renderDetailHarvest
     * Method for render page with details about harvest.
     *
     * @param int $id ID of the harvest to show details for.
     * @return void
     */
    public function renderDetailHarvest(int $id): void
    {
        $harvest = $this->taskRepository->fetchById($id);

        if ($harvest === null || !($harvest instanceof Harvest)) {
            $this->flashMessage('Chyba při načítání sklizně z databáze, zkuste to prosím znovu.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
            return;
        }

        $this->template->task = $harvest;

        // Get task issuer to latte template
        $this->template->taskIssuer = ($harvest->getIdTaskIssuer() !== null)
            ? $this->userRepository->getUserById($harvest->getIdTaskIssuer())
            : null;
        
        // Get task confirmer to latte template
        $this->template->taskConfirmer = ($harvest->getIdTaskConfirmer() !== null) 
            ? $this->userRepository->getUserById($harvest->getIdTaskConfirmer()) 
            : null;
        
        // Get harvest vintner <=> confimer to latte template
        $this->template->harvestConfirmer = ($harvest->getIdHarvestConfirmer() !== null) 
            ? $this->userRepository->getUserById($harvest->getIdHarvestConfirmer()) 
            : null;

        // Get wine to latte template
        $this->template->wine = ($harvest->getIdWine() !== null)
            ? $this->wineRep->fetchWineById($harvest->getIdWine())
            : null;
        
        // Get wine rows of vineyard where the harvest was made
        $this->template->wineRows = $this->vineyardRep->getWineRowsOnVineyard($harvest->getVineyard()->getId());
    }   


    /**
     * renderDetailBottling
     * Method for render page with details about bottling.
     *
     * @param int $id ID of the bottling to show details for.
     * @return void
     */
    public function renderDetailBottling(int $id): void
    {
        $bottling = $this->taskRepository->fetchById($id);

        if ($bottling === null) {
            $this->flashMessage('Chyba při načítání lahvování z databáze, zkuste to prosím znovu.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
            return;
        }

        $this->template->task = $bottling;
        
        // Get task issuer to latte template
        $this->template->taskIssuer = ($bottling->getIdTaskIssuer() !== null) 
            ? $this->userRepository->getUserById($bottling->getIdTaskIssuer()) 
            : null;
        
        // Get task confirmer to latte template
        $this->template->taskConfirmer = ($bottling->getIdTaskConfirmer() !== null) 
            ? $this->userRepository->getUserById($bottling->getIdTaskConfirmer()) 
            : null;
    }


    /**
     * renderDetailTreatment
     * Method for render page with details about treatment.
     *
     * @param int $taskId ID of the treatment to show details for.
     * @return void
     */
    public function renderDetailTreatment(int $id): void
    {
        /** @var Treatment */
        $treatment = $this->taskRepository->fetchById($id);

        if ($treatment === null) {
            $this->flashMessage('Chyba při načítání ošetření z databáze, zkuste to prosím znovu.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
            return;
        }

        $this->template->task = $treatment;
        
        // Get task issuer to latte template
        $this->template->taskIssuer = ($treatment->getIdTaskIssuer() !== null) 
            ? $this->userRepository->getUserById($treatment->getIdTaskIssuer()) 
            : null;

        // Get task confirmer to latte template
        $this->template->taskConfirmer = ($treatment->getIdTaskConfirmer() !== null) 
            ? $this->userRepository->getUserById($treatment->getIdTaskConfirmer()) 
            : null;

        // Get wine rows of vineyard where the treatment was made
        $this->template->wineRows = $this->vineyardRep->getWineRowsOnVineyard($treatment->getVineyard()->getId());
    }


   /**
    * formError
    * Method for handling form errors.
    *
    * @param Form $form Form where the error was detected.
    * @return void
    */
    public function formError(Form $form): void
    {
        $errors = $form->getErrors();
 
            if (!empty($errors)) {
                $this->flashMessage($errors[0], FlashMsgType::ERROR->value);
            } else {
                $this->flashMessage('Došlo k neznámé chybě při odesílání formuláře.', FlashMsgType::ERROR->value);
            }
    }

    
// ----------------------------------------------------------
//                       Bottling Task 
// ----------------------------------------------------------

    public function actionStartBottling(int $wineId) {
        // check if bottling is planned
        $bottling = $this->bottlingRepository->getUnfinishedBottlingTaskForWine($wineId);
        if ($bottling !== null) {
            $this->redirect('markTaskDone', $bottling->getId());
        }

        $section = $this->session->getSection("TaskCreation");
        $section->set("newTask", null);
        $task = $this->fetchCurrentNewTask();
        $task->setIdWine($wineId);
        $task->setType(TaskType::BOTTLING);
        $this->redirect("addTask1");
    }

    public function createComponentWineSelectForm() 
    {
        $availableWines = $this->wineRep->fetchAllProductionWines();
        bdump($availableWines);
        $form = new Form();
        
        $selectArray = [];

        foreach ($availableWines as $wine) {
            $selectArray[$wine->getId()] = $wine->getName();
        }
        
        $form->addSelect("wine", "Víno", $selectArray)
        ->setRequired("Prosím zadejte víno")
        ->setHtmlAttribute('class', 'form-select')
        ->setHtmlAttribute('placeholder', 'Vyberte víno');
    
        $form->addUpload("image", "Obrázek vína")
        ->addRule($form::Image, "Nahrajte obrázek vína")
        ->setHtmlAttribute('class', 'form-control')
        ->setHtmlAttribute('placeholder', 'Nahrajte obrázek odrůdy vína');
    
        $form->addFloat('volume', 'Objem láhve')
        ->addRule(Form::Min, "minimum", ValidationDefinitions::VOLUME_MIN)
        ->addRule(Form::Max, "maximum", ValidationDefinitions::VOLUME_MAX)
        ->setRequired('zadejte objem jedné láhve')
        ->setHtmlAttribute('class', 'form-control')
        ->setHtmlAttribute('placeholder', 'zadejte objem jedné láhve v ml');
    
        $form->addSubmit("finish", "Pokračovat")
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');

        $form->onSuccess[] = [$this, "processBottlingForm"];
        return $form;
    }
    
    public function renderBottling() {
        $task = $this->fetchCurrentNewTask();
        $form = $this->getComponent("wineSelectForm");
        bdump($task->getIDWine());
        try {
            $form["wine"]->setValue($task->getIDWine());
        } catch (\Exception $e) {
            $this->flashMessage('Vybrané víno nelze zalahvovat, případně již pro něj existuje úkon lahvování.', FlashMsgType::ERROR->value);
            Debugger::log('Wine select value setting failed: ' . $e->getMessage(), ILogger::ERROR);
            $this->redirect('dashboard');
        }
        
        $form["volume"]->setValue($task->getBottleVolume());
    }
    
    public function processBottlingForm(Form $form, \stdClass $values) {
        
        $task = $this->fetchCurrentNewTask();
        
        if (isset($values->wine)) {
            $task->setIdWine($values->wine);
        }

        $image = $values->image;
        $imagePath = null;
        if ($image->hasFile()) {
            $imagePath = $this->imgRep->uploadImage($values->image);
            if ($imagePath === '') {
                Debugger::log('Image upload failed', ILogger::ERROR);
                $form->addError('Obrázek se nepodařilo nahrát.');
                return;
            }
            $wine = $this->wineRep->fetchWineById($task->getIdWine());
            $imageEntity = new Image(null, $imagePath, $wine->name, 'Obrázek vína ' . $wine->name);
            $task->setImg($imageEntity);
        }
        
        if (isset($values->volume)) {
            $task->setBottleVolume($values->volume);
        }
        
        if (isset($values->wine)) {
            $task->setIdWine($values->wine);
        }
        
        $this->finalizeTask();
    }

// ----------------------------------------------------------
//                      Treatment Task 
// ----------------------------------------------------------
    public function renderTreatment() {
        /** @var TaskData $task */
        $task = $this->fetchCurrentNewTask();

        $vineYardFrom = $this->getComponent("selectVineyard");
        $wineRowsForm = $this->getComponent("selectRowsForm");

        if ($task->getIdWineYard()) {
            $vineYardFrom['vineyard']->setValue($task->getIdWineYard());
        }

        // prepare selected rows for template display
        $selectedRows = [];
        $idRows = $task->getIdRows() ?? [];
        foreach ($idRows as $code) {
            $rowObj = $this->vineyardRep->getWineRowById($task->getIdWineYard(), $code);
            if ($rowObj !== null) {
                $selectedRows[] = $rowObj;
            }
        }
        $this->template->selectedRows = $selectedRows;
    }

    public function submitTreatmentRows(Button $button, \stdClass $values) {
        $task = $this->fetchCurrentNewTask();

         if (!$task->getIdWineYard() || !$task->getIdRows()) {
            $this->flashMessage("Je nutné vybrat vinici a alespoň jeden řádek.", FlashMsgType::ERROR->value);
            return;
        }

        $this->redirect("treatmentFinal");
    }


    public function createComponentTreatmentDetailsForm(): Form {
        $form = new Form();

        $form->addSelect("type", "Typ ošetření", [
            TreatmentType::SPRAYING->value => "Postřik",
            TreatmentType::FERTILIZING->value => "Hnojení",
            TreatmentType::CUTTING->value => "Řez",
            TreatmentType::WEEDING->value => "Odplevelení",
            TreatmentType::OTHER->value => "Ostatní",
        ])
            ->setRequired("Zvolte typ ošetření")
            ->setHtmlAttribute('class', 'form-select')
            ->setDefaultValue(TreatmentType::SPRAYING->value)
            ->setHtmlAttribute('placeholder', 'Zvolte typ ošetření');
        
        $form->addText('name', 'Název ošetření')
            ->addRule(Form::MinLength, 'Název ošetření musí mít alespoň %d znaků.', ValidationDefinitions::TREATMENT_NAME_LENGTH_MIN)
            ->addRule(Form::MaxLength, 'Název ošetření může mít maximálně %d znaků.', ValidationDefinitions::TREATMENT_NAME_LENGTH_MAX)
            ->setRequired('Zadejte název ošetření.')
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", "Zadejte název ošetření ...");
        
        $form->addFloat('amount', 'Množství')
            ->addRule(Form::Min, 'Množství použitých prostředků musí být alespoň %d.', ValidationDefinitions::TREATMENT_QUANTITY_MIN)
            ->addRule(Form::Max, 'Množství použitých prostředků může být maximálně %d.', ValidationDefinitions::TREATMENT_QUANTITY_MAX)
            ->setRequired('Zadejte množství použitých prostředků.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte množství použitých prostředků ...');

        $form->addText("unit", "Jednotka množství")
            ->addRule(Form::MinLength, 'Jednotka množství musí mít alespoň %d znaků.', ValidationDefinitions::TREATMENT_UNIT_LENGTH_MIN)
            ->addRule(Form::MaxLength, 'Jednotka množství může mít maximálně %d znaků.', ValidationDefinitions::TREATMENT_UNIT_LENGTH_MAX)
            ->setRequired("Zadejte jednotku množství (např. litr, kg, ...)")
            ->setHtmlAttribute("class", "form-control")
            ->setHtmlAttribute("placeholder", "Zadejte jednotku množství ...");

        $form->addSubmit("finish", "Dokončit ošetření")
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold')
            ->onClick[] = [$this, "treatmentDetailsFinalize"];

        $form->addSubmit("cancel", "Zrušit")
            ->setHtmlAttribute('class', 'btn btn-danger w-100 fw-bold')
            ->onClick[] = [$this, "handleClearForm"];
        
        $form->addSubmit("back", "Zpět")
            ->setHtmlAttribute('class', 'btn btn-secondary w-100 fw-bold')
            ->onClick[] = [$this, "treatmentGoBackToRowsSelection"];

        
        return $form;
    }

    public function obtainDataFromTreatmentDetailsForm(\stdClass $values): ?TaskData {
        $task = $this->fetchCurrentNewTask();
        try {
            if (isset($values->type)) {
                $task->setTreatmentType(TreatmentType::from((string) $values->type));
            }

            if (isset($values->name)) {
                $task->setTreatmentName((string) $values->name);
            }

            if (isset($values->amount)) {
                $task->setAmount((float) $values->amount);
            }

            if (isset($values->unit)) {
                $task->setUnit((string) $values->unit);
            }

        } catch (\Exception $e) {
            Debugger::log('Error obtaining treatment details from form: ' . $e->getMessage(), ILogger::ERROR);
            return null;
        }
        
        return $task;
    }

    public function treatmentGoBackToRowsSelection(Button $button, \stdClass $values) {
        $this->obtainDataFromTreatmentDetailsForm($values);
        $this->redirect("treatment");
    }

    public function treatmentDetailsFinalize(Button $button, \stdClass $values) {
        $task = $this->obtainDataFromTreatmentDetailsForm($values);
        if ($task === null) {
            $this->flashMessage('Chyba při získávání údajů o ošetření.', 'error');
            return;
        }
        $result = $this->taskRepository->createTreatmentTask($task, $this->user->getId());
        if ($result) {
            $this->flashMessage('Ošetření bylo úspěšně vytvořeno.', FlashMsgType::SUCCESS->value);
            $this->redirect('dashboard');
        } else {
            $this->flashMessage('Chyba při vytváření ošetření, zkuste to prosím znovu.', FlashMsgType::ERROR->value);
        }
    }

    public function renderTreatmentFinal() {
        /** @var TaskData $task */
        $task = $this->fetchCurrentNewTask();

        // set default values for form
        $form = $this->getComponent('treatmentDetailsForm');

        $form['type']->setValue($task->getTreatmentType()?->value);
        $form['name']->setValue($task->getTreatmentName());
        $form['amount']->setValue($task->getAmount());
        $form['unit']->setValue($task->getUnit());

        /** @var Vineyard $vineyard */
        $vineyard = $this->vineyardRep->fetchById($task->getIdWineYard());

        $this->template->vineyard = $vineyard;
        $this->template->task = $task;
        // todo
    }

    
// ----------------------------------------------------------
//                       Harvest Task 
// ----------------------------------------------------------

    public function renderHarvest() {
        $task = $this->fetchCurrentNewTask();

        $vineYardFrom = $this->getComponent("selectVineyard");
        $wineVarietyFrom = $this->getComponent("selectWineVariety");

        if ($task->getIdWineYard()) {
            $vineYardFrom['vineyard']->setValue($task->getIdWineYard());
        }

        if ($task->getIdWineVariety()) {
            $wineVarietyFrom['wineVariety']->setValue($task->getIdWineVariety());
        }

        // prepare selected rows for template display
        $selectedRows = [];
        $idRows = $task->getIdRows() ?? [];
        foreach ($idRows as $code) {
            $rowObj = $this->vineyardRep->getWineRowById($task->getIdWineYard(), $code);
            if ($rowObj !== null) {
                $selectedRows[] = $rowObj;
            }
        }
        $this->template->selectedRows = $selectedRows;
       
    }
    
    public function createComponentSelectVineyard() {
        $form = new Form();
        
        $options = [];
        foreach ($this->vineyardRep->fetchAll() as $vineYard) {
            if (!$vineYard->isActive()) {
                continue;
            }
            $options[$vineYard->getId()] = $vineYard->getName();
        }

        $form->addSelect("vineyard", "Vinohrad", ['' => 'Vyberte vinohrad ...'] + $options)
        ->setRequired("Prosím vyberte vinohrad")
        ->setHtmlAttribute("class", "form-select")
        ->setHtmlAttribute('placeholder', 'Vyberte Vinohrad')
        ->setDefaultValue('');
    
        $form->addSubmit("submit", "Vybrat")
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = [$this, "selectVineyard"];

        
        return $form;
    }
    
    public function selectVineYard(Form $form, \stdClass $values) {
        $task = $this->fetchCurrentNewTask();
        
        if (isset($values->vineyard)) {
            $task->setIdWineYard($values->vineyard);
            $task->setIdWineVariety(null);
            $task->setIdRows([]);
        }
        $this->redirect("this");
    }
    
    public function createComponentSelectWineVariety() {
        
        $task = $this->fetchCurrentNewTask();
        $form = new Form();
        
        $options = [];
        if ($task->getIdWineYard()) {
            foreach($this->vineyardRep->getUniqueWineVarietiesOnVineyard($task->getIdWineYard()) as $variety) {
                $options[$variety->getId()] = $variety->getName();
            }
        }
        
        $form->addSelect("wineVariety", "Odrůda", ['' => "Vyberte odrůdu ... "] + $options)
        ->setRequired("Prosím zadejte víno")
        ->setHtmlAttribute('class', 'form-select')
        ->setHtmlAttribute('placeholder', 'Vyberte odrůdu');
    
        $form->addSubmit("submit", "vybrat");
    
        $form->onSuccess[] = [$this, "selectVineVariety"];
        
        return $form;
    }
    
    public function selectVineVariety(Form $form, \stdClass $values) {
        $task = $this->fetchCurrentNewTask();
       
        if (isset($values->wineVariety)) {
            $task->setIdWineVariety($values->wineVariety);
            $task->setIdRows([]);
        }
        $this->redirect("this");
    }
    
    public function createComponentSelectRowsForm() {
        $task = $this->fetchCurrentNewTask();
        $wineYardId = $task->getIdWineYard();
        $wineVarietyId = $task->getIdWineVariety();
       
        $options = [];
        switch ($task->getType()) {
            case TaskType::HARVEST:
                if ($wineVarietyId && $wineYardId) {
                    $rows = $this->vineyardRep->getRowsAtVineyardWithWineVariety($wineYardId, $wineVarietyId);
                    $already = $task->getIdRows() ?? [];
                    foreach ($rows as $r) {
                        // exclude non active rows
                        if (!$r->isActive()) {
                            continue;
                        }
                        $code = $r->getId();
                        if (in_array($code, $already, true)) {
                            continue; // exclude already-selected rows
                        }
                        $options[$code] = 'Řádek ' . $code . ' — ' . $r->getHeadCount() . ' keřů (' . $r->getYearPlanted() . ')';
                    }
                }
                break;
            case TaskType::TREATMENT:
                if ($wineYardId) {
                    $rows = $this->vineyardRep->getWineRowsOnVineyard($wineYardId);
                    $already = $task->getIdRows() ?? [];
                    foreach ($rows as $r) {
                        // exclude non active rows
                        if (!$r->isActive()) {
                            continue;
                        }
                        $code = $r->getId();
                        if (in_array($code, $already, true)) {
                            continue; // exclude already-selected rows
                        }
                        $options[$code] = 'Řádek ' . $code . ' — ' . $r->getHeadCount() . ' keřů (' . $r->getYearPlanted() . ')';
                    }
                }
                break;
            default:
                break;
        }

        $form = new Form();

        $form->addSelect('rows', 'Řádky', ['' => 'Vyberte řádek ...'] + $options)
            ->setHtmlAttribute('class', 'form-select')
            ->setDefaultValue('');
        
        $form->addSubmit('backToDashboard', 'Zrušit')
            ->setHtmlAttribute('class', 'btn btn-danger w-100 fw-bold')
            ->onClick[] = [$this, 'handleClearForm'];

        $form->addSubmit('addRow', 'Přidat')
            ->setHtmlAttribute('class', 'btn btn-primary mt-2')
            ->onClick[] = [$this, 'wineRowSelection'];

        $form->addSubmit('clearRows', 'Vymazat výběr')
            ->setHtmlAttribute('class', 'btn btn-outline-danger mt-2')
            ->onClick[] = [$this, 'clearRows'];
        
        switch ($task->getType()) {
            case TaskType::HARVEST:
                $form->addSubmit("next", "Uložit sklizeň")
                    ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold')
                    ->onClick[] = [$this, 'submitHarvestDetails'];
                break;
            case TaskType::TREATMENT:
                $form->addSubmit("next", "Pokračovat k detailům ošetření")
                    ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold')
                    ->onClick[] = [$this, 'submitTreatmentRows'];
                break;
            default:
                break;
        }
        
        $form->addSubmit("goBack", "Zpět")
            ->setHtmlAttribute('class', 'btn btn-secondary w-100 fw-bold')
            ->onClick[] = [$this, "goBackToWorkers"];
        
        return $form;
    }

    public function submitHarvestDetails(Button $button, \stdClass $values) {
        
        $task = $this->fetchCurrentNewTask();
        
        if (!$task->getIdWineYard() || !$task->getIdWineVariety() || !$task->getIdRows()) {
            $this->flashMessage("Chybné údaje!", FlashMsgType::ERROR->value);
            $this->redirect("this");
            return;
        }
        
        $this->finalizeTask($task);
    }
    
    public function goBackToWorkers(Button $button, \stdClass $values) {
        $this->redirect("selectWorkers");
    }
    
    public function clearRows() {
       $task = $this->fetchCurrentNewTask(); 
       $task->setIdRows([]);
    }
    
    public function wineRowSelection(Button $button, \stdClass $values): void {

        // add the row to the array 
        if (!isset($values->rows) || $values->rows === "") {
            $this->flashMessage("Nevybrali jste žádný řádek", FlashMsgType::ERROR->value);
            $this->redirect("this");
            return;
        }
        
        $id = (int) $values->rows;
        /** @var TaskData */
        $newTask = $this->fetchCurrentNewTask();
        $ids = $newTask->getIdRows() ?? [];
        if (!in_array($id, $ids, true)) {
            $ids[] = $id;
            $newTask->setIdRows($ids);
            $this->session->getSection('TaskCreation')->set('newTask', $newTask);
            $this->flashMessage('Řádek přidán.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Tento řádek je již přidán.', FlashMsgType::ERROR->value);
        }
        $this->redirect("this");
    }

    public function handleRemoveRowFromSelection(int $rowCode): void
    {
        $newTask = $this->fetchCurrentNewTask();
        $ids = $newTask->getIdRows() ?? [];
        $updated = array_values(array_filter($ids, fn($i) => $i !== $rowCode));
        $newTask->setIdRows($updated);
        $this->session->getSection('TaskCreation')->set('newTask', $newTask);
        $this->flashMessage('Řádek odebrán.', FlashMsgType::SUCCESS->value);
        $this->redirect('this');
    }
    

    // HARVEST MARKING
    public function createComponentMarkHarvestDoneForm(): Form
    {
        $form = new Form();
        
        $form->addFloat('quantity', 'Množství sklizených hroznů (kg)')
            ->setRequired('Zadejte množství sklizených hroznů.')
            ->addRule(Form::Min, 'Množství musí být minimálně %d kg.', ValidationDefinitions::HARVEST_WEIGHT_MIN)
            ->addRule(Form::Max, 'Množství nesmí přesáhnout %d kg.', ValidationDefinitions::HARVEST_WEIGHT_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte množství sklizených hroznů v kg');
        
        $form->addFloat('sugar_content', 'Cukernatost (°NM)')
            ->setRequired('Zadejte cukernatost.')
            ->addRule(Form::Min, 'Cukernatost musí být minimálně %d °NM.', ValidationDefinitions::SUGAR_CONTENT_MIN)
            ->addRule(Form::Max, 'Cukernatost nesmí přesáhnout %d °NM.', ValidationDefinitions::SUGAR_CONTENT_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte cukernatost hroznů v °NM');
    
        $form->addSubmit('send', 'Uložit')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');
    
        $form->onSuccess[] = [$this, 'onSuccessMarkHarvestDoneForm'];
    
        return $form;
    }

    public function onSuccessMarkHarvestDoneForm(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('K označení sklizně jako dokončené nemáte oprávnění.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
            return;
        }

        $id = (int)$this->getParameter('id');

        /** @var Harvest $harvest */
        $harvest = $this->harvestRepository->fetchById($id);
        if ($harvest === null) {
            $this->flashMessage('Sklizeň nebyla nalezena.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
            return;
        }

        $result = $this->harvestRepository->markHarvestDone(
            $id,
            (float)$values->quantity,
            (float)$values->sugar_content,
            $this->user->getId()
        );
        if ($result) {
            $this->flashMessage('Sklizeň byla úspěšně označena jako dokončená.', FlashMsgType::SUCCESS->value);
            $this->redirect('Tasks:dashboard');
        } else {
            $this->flashMessage('Při označování sklizně došlo k chybě.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
        }
    }

    public function renderMarkHarvestDone(int $id): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('K označení sklizně jako dokončené nemáte oprávnění.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
            return;
        }

        /** @var Harvest $harvest */
        $harvest = $this->harvestRepository->fetchById($id);
        if ($harvest === null) {
            $this->flashMessage('Sklizeň nebyla nalezena.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
            return;
        }

        $this->template->harvest = $harvest;
    }

    public function createComponentMarkTaskDoneForm(): Form
    {
        $form = new Form();

        $form->addDateTime('execution_date', 'Datum provedení')
            ->setValue(new DateTime('now'))
            ->setRequired('Zadejte datum provedení.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addSubmit('send', 'Zaznamenat splnění')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');
        
        $form->onSuccess[] = [$this, 'onSuccessMarkTaskDoneForm'];

        return $form;
    }

    public function onSuccessMarkTaskDoneForm(Form $form, \stdClass $values): void
    {
        $id = (int)$this->getParameter('id');

        /** @var Task $task */
        $task = $this->taskRepository->fetchById($id);
        if ($task === null) {
            $this->flashMessage('Úkon nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
            return;
        }

        $result = $this->taskRepository->markTaskDone(
            $task,
            $this->user->getId(),
            DateTime::from($values->execution_date)
        );

        if ($result) {
            $this->flashMessage('Úkon byl úspěšně označen jako dokončený.', FlashMsgType::SUCCESS->value);
            $this->redirect('Tasks:dashboard');
        } else {
            $this->flashMessage('Při označování úkonu došlo k chybě.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
        }
    }

    public function renderMarkTaskDone(int $id): void
    {
        // all vintners can mark tasks as done
        // only workers assigned to the task can mark it as done
        if (!$this->user->isInRole(Role::VINTNER->name) &&
            !($this->user->isInRole(Role::WORKER->name) &&
            $this->taskRepository->isUserAssignedToTask($this->user->getId(), $id))) {
            $this->flashMessage('K označení úkonu jako dokončeného nemáte oprávnění.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
            return;

        }

        /** @var Task $task */
        $task = $this->taskRepository->fetchById($id);
        if ($task === null) {
            $this->flashMessage('Úkon nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('Tasks:dashboard');
            return;
        }

        $this->template->task = $task;
    }

// ----------------------------------------------------------
//                      Finalization 
// ----------------------------------------------------------
    
    public function finalizeTask() {
        $task = $this->fetchCurrentNewTask();
        $result = false;
        
        switch($task->getType()) {
            case TaskType::BOTTLING: 
                $result = $this->taskRepository->createBottlingTask($task, $this->user->getId());
                break; 
            case TaskType::HARVEST: 
                $result = $this->taskRepository->createHarvestTask($task, $this->user->getId());
                break; 
            case TaskType::TREATMENT:
                $result = $this->taskRepository->createTreatmentTask($task, $this->user->getId());
                break;
            default: 
                break;
        }
        
        if (!$result) {
            $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Úkon uložen", FlashMsgType::SUCCESS->value);
        }

        $this->handleClearForm();
        $this->redirect("dashboard");
    }
}


