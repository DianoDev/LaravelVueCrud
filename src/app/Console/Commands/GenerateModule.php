<?php

namespace DianoDev\app\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateModule extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Cria a estrutura de arquivos para um módulo com Inertia.js';

    public function handle()
    {
        $moduleName = $this->argument('name');
        $moduleNameCaps = ucfirst($moduleName);

        $tableName = $this->ask("Qual é o nome da tabela no banco de dados?");

        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

        if (empty($columns)) {
            $this->error("Nenhuma coluna encontrada na tabela '{$tableName}'. Verifique o nome e tente novamente.");
            return 1;
        }

        $formColumns = $this->choice(
            'Quais colunas devem ser incluídas no formulário ($form)?',
            $columns,
            null,
            null,
            true
        );

        $gridColumns = $this->choice(
            'Quais colunas devem ser incluídas no grid ($grid)?',
            $columns,
            null,
            null,
            true
        );

        $paths = [
            "app/Databases/Contracts/{$moduleNameCaps}Contract.php" => $this->getContractContent($moduleName),
            "app/Databases/Models/{$moduleNameCaps}.php" => $this->getModelContent($moduleName, $tableName),
            "app/Databases/Repositories/{$moduleNameCaps}Repository.php" => $this->getRepositoryContent($moduleName, $formColumns),
            "app/Http/Controllers/Admin/{$moduleNameCaps}Controller.php" => $this->getControllerContent($moduleName, $formColumns, $tableName),
            "app/Http/Requests/{$moduleNameCaps}Request.php" => $this->getRequestContent($moduleName),
            "resources/js/pages/admin/{$moduleNameCaps}/{$moduleNameCaps}Index.vue" => $this->getIndexPageContent($moduleName, $gridColumns, $tableName),
            "resources/js/pages/admin/{$moduleNameCaps}/{$moduleNameCaps}Form.vue" => $this->getFormContent($moduleName, $formColumns, $tableName),
        ];

        foreach ($paths as $path => $content) {
            $this->createFile($path, $content);
        }

        $this->addRoute($moduleName, $tableName);
        $this->addBindings($moduleName);

        $this->info("Módulo '{$moduleName}' criado com sucesso no padrão Inertia.js!");
        return 0;
    }

    private function createFile($path, $content)
    {
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        if (!File::exists($path)) {
            File::put($path, $content);
            $this->info("Arquivo criado: {$path}");
        } else {
            $this->warn("Arquivo já existe: {$path}");
        }
    }

    private function getContractContent($moduleName)
    {
        $className = ucfirst($moduleName);
        return "<?php\n\n" .
            "namespace App\\Databases\\Contracts;\n\n" .
            "use Illuminate\\Database\\Eloquent\\Model;\n" .
            "use Illuminate\\Pagination\\LengthAwarePaginator;\n\n" .
            "interface {$className}Contract\n" .
            "{\n" .
            "    public function paginate(array \$pagination = [], array \$columns = ['*']): LengthAwarePaginator;\n" .
            "    public function getAll();\n" .
            "    public function getById(int \$id): Model;\n" .
            "    public function create(array \$params, bool \$autoCommit = true): bool;\n" .
            "    public function update(int \$id, array \$params, bool \$autoCommit = true): bool;\n" .
            "    public function destroy(int \$id, bool \$autoCommit = true): bool;\n" .
            "}\n";
    }

    private function getModelContent($moduleName, $tableName)
    {
        $className = ucfirst($moduleName);
        $tableName = strtolower($tableName);
        $sequenceName = "{$tableName}_id_seq";

        return "<?php\n\n" .
            "namespace App\\Databases\\Models;\n\n" .
            "use Illuminate\\Database\\Eloquent\\Model;\n" .
            "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n\n" .
            "class {$className} extends Model\n" .
            "{\n" .
            "    use SoftDeletes;\n\n" .
            "    protected \$primaryKey = \"id\";\n" .
            "    protected \$table = '{$tableName}';\n" .
            "    public string \$sequence = '{$sequenceName}';\n" .
            "    protected \$guarded = [];\n" .
            "}\n";
    }

    private function getRepositoryContent($moduleName, $formColumns)
    {
        $className = ucfirst($moduleName);
        $modelVariable = lcfirst($moduleName);
        $createFields = implode(",\n", array_map(function ($field) {
            return "                '{$field}' => \$params['{$field}']";
        }, $formColumns));

        $filterConditions = implode("\n", array_map(function ($field) {
            return "        if (isset(\$pagination['{$field}'])) {\n" .
                "            \$keyword = mb_strtolower(\$pagination['{$field}']);\n" .
                "            \$query->whereRaw('lower({$field}) like ?', [\"%{\$keyword}%\"]);\n" .
                "        }";
        }, $formColumns));

        return "<?php\n" .
            "namespace App\\Databases\\Repositories;\n\n" .
            "use App\\Databases\\Contracts\\{$className}Contract;\n" .
            "use App\\Databases\\Models\\{$className};\n" .
            "use Illuminate\\Database\\Eloquent\\Collection;\n" .
            "use Illuminate\\Database\\Eloquent\\Model;\n" .
            "use Illuminate\\Pagination\\LengthAwarePaginator;\n" .
            "use Illuminate\\Support\\Facades\\DB;\n" .
            "use Exception;\n\n" .
            "class {$className}Repository implements {$className}Contract\n" .
            "{\n" .
            "    public function __construct(private {$className} \${$modelVariable})\n" .
            "    {\n" .
            "    }\n\n" .
            "    public function getById(int \$id): Model\n" .
            "    {\n" .
            "        return {$className}::query()\n" .
            "            ->where('id', '=', \$id)\n" .
            "            ->firstOrFail();\n" .
            "    }\n\n" .
            "    public function getAll(): Collection\n" .
            "    {\n" .
            "        return {$className}::query()->get();\n" .
            "    }\n\n" .
            "    public function paginate(array \$pagination = [], array \$columns = ['*']): LengthAwarePaginator\n" .
            "    {\n" .
            "        \$query = {$className}::query();\n\n" .
            $filterConditions . "\n\n" .
            "        \$query->orderBy(\$pagination['sort'] ?? '{$formColumns[0]}', \$pagination['sort_direction'] ?? 'asc');\n" .
            "        return \$query->paginate(\$pagination['per_page'] ?? 10, \$columns, 'page', \$pagination['current_page'] ?? 1);\n" .
            "    }\n\n" .
            "    public function create(array \$params, bool \$autoCommit = true): bool\n" .
            "    {\n" .
            "        \$autoCommit && DB::beginTransaction();\n" .
            "        try {\n" .
            "            \${$modelVariable} = new {$className}([\n" .
            $createFields . "\n" .
            "            ]);\n" .
            "            \${$modelVariable}->save();\n\n" .
            "            \$autoCommit && DB::commit();\n" .
            "            return true;\n" .
            "        } catch (Exception \$ex) {\n" .
            "            \$autoCommit && DB::rollBack();\n" .
            "            throw new Exception(\$ex);\n" .
            "        }\n" .
            "    }\n\n" .
            "    public function update(int \$id, array \$params, bool \$autoCommit = true): bool\n" .
            "    {\n" .
            "        \$autoCommit && DB::beginTransaction();\n" .
            "        try {\n" .
            "            \${$modelVariable} = \$this->getById(\$id);\n" .
            "            \${$modelVariable}->update(\$params);\n\n" .
            "            \$autoCommit && DB::commit();\n" .
            "            return true;\n" .
            "        } catch (Exception \$ex) {\n" .
            "            \$autoCommit && DB::rollBack();\n" .
            "            throw new Exception(\$ex);\n" .
            "        }\n" .
            "    }\n\n" .
            "    public function destroy(int \$id, bool \$autoCommit = true): bool\n" .
            "    {\n" .
            "        \$autoCommit && DB::beginTransaction();\n" .
            "        try {\n" .
            "            \${$modelVariable} = \$this->getById(\$id);\n" .
            "            \${$modelVariable}->delete();\n" .
            "            \$autoCommit && DB::commit();\n" .
            "        } catch (Exception \$ex) {\n" .
            "            \$autoCommit && DB::rollBack();\n" .
            "            throw new Exception(\$ex->getMessage());\n" .
            "        }\n\n" .
            "        return true;\n" .
            "    }\n" .
            "}\n";
    }

    private function getControllerContent(string $modulo, $formContent, $tableName): string
    {
        $className = ucfirst($modulo);
        $variableName = lcfirst($modulo);
        $filterOptions = implode(",\n", array_map(function ($field) {
            return "            '{$field}' => [\n                'type' => 'text',\n            ]";
        }, $formContent));

        return "<?php\n" .
            "namespace App\\Http\\Controllers\\Admin;\n\n" .
            "use App\\Http\\Controllers\\Controller;\n" .
            "use Illuminate\\Http\\Request;\n" .
            "use Illuminate\\Http\\JsonResponse;\n" .
            "use App\\Databases\\Contracts\\{$className}Contract;\n" .
            "use App\\Http\\Requests\\{$className}Request;\n" .
            "use Inertia\\Inertia;\n" .
            "use Inertia\\Response;\n\n" .
            "class {$className}Controller extends Controller\n" .
            "{\n" .
            "    public function __construct(private readonly {$className}Contract \${$variableName}Repository)\n" .
            "    {\n" .
            "    }\n\n" .
            "    public function index(): Response\n" .
            "    {\n" .
            "        return Inertia::render('admin/{$className}/{$className}Index');\n" .
            "    }\n\n" .
            "    public function list(Request \$request): JsonResponse\n" .
            "    {\n" .
            "        \$dados = \$this->{$variableName}Repository->paginate(\$request->all())->toArray();\n" .
            "        \$dados['filter_options'] = [\n" .
            $filterOptions . "\n" .
            "        ];\n" .
            "        return response()->json(\$dados);\n" .
            "    }\n\n" .
            "    public function create({$className}Request \$request): JsonResponse\n" .
            "    {\n" .
            "        \$params = \$request->except('_token');\n" .
            "        \$this->{$variableName}Repository->create(\$params);\n" .
            "        return response()->json(['success' => true, 'message' => '{$className} criado com sucesso!']);\n" .
            "    }\n\n" .
            "    public function edit(int \$id): JsonResponse\n" .
            "    {\n" .
            "        \$registro = \$this->{$variableName}Repository->getById(\$id);\n" .
            "        return response()->json(\$registro);\n" .
            "    }\n\n" .
            "    public function update({$className}Request \$request, int \$id): JsonResponse\n" .
            "    {\n" .
            "        \$params = \$request->validated();\n" .
            "        \$this->{$variableName}Repository->update(\$id, \$params);\n" .
            "        return response()->json(['success' => true, 'message' => '{$className} atualizado com sucesso!']);\n" .
            "    }\n\n" .
            "    public function delete(int \$id): JsonResponse\n" .
            "    {\n" .
            "        \$this->{$variableName}Repository->destroy(\$id);\n" .
            "        return response()->json(['success' => true, 'message' => '{$className} excluído com sucesso!']);\n" .
            "    }\n" .
            "}\n";
    }

    private function getRequestContent(string $modulo): string
    {
        $className = ucfirst($modulo);

        return "<?php\n" .
            "namespace App\\Http\\Requests;\n\n" .
            "use Illuminate\\Foundation\\Http\\FormRequest;\n\n" .
            "class {$className}Request extends FormRequest\n" .
            "{\n" .
            "    public function rules(): array\n" .
            "    {\n" .
            "        return [\n" .
            "            // Adicione suas regras de validação aqui\n" .
            "            // 'nome' => ['required', 'string', 'max:128'],\n" .
            "        ];\n" .
            "    }\n\n" .
            "    public function authorize(): bool\n" .
            "    {\n" .
            "        return true;\n" .
            "    }\n\n" .
            "    public function messages(): array\n" .
            "    {\n" .
            "        return [];\n" .
            "    }\n" .
            "}\n";
    }

    private function getIndexPageContent(string $moduleName, $gridColumns, $tableName): string
    {
        $output = preg_replace('/([A-Z])/', ' $1', $moduleName);
        $className = ucfirst($moduleName);
        $componentName = str_replace('_', '-', $tableName);

        $gridFields = "";
        foreach ($gridColumns as $column) {
            $title = ucwords(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $column)));
            $gridFields .= "    {name: '{$column}', title: '" . ucfirst($title) . "', width: '20%', sort: '{$column}', nowrap: true},\n";
        }

        return "<template>\n" .
            "    <LayoutPrincipal>\n" .
            "        <div class=\"page-content\">\n" .
            "            <div class=\"flex items-center justify-between mb-4 w-100\">\n" .
            "                <h2 class=\"text-2xl font-semibold text-primary\"></h2>\n" .
            "                <div class=\"flex\">\n" .
            "                    <popup-button\n" .
            "                        id=\"novo-{$componentName}\"\n" .
            "                        title=\"Novo {$output}\"\n" .
            "                        size=\"xl\"\n" .
            "                        component=\"{$className}Form\"\n" .
            "                        variant=\"secondary\"\n" .
            "                    >\n" .
            "                        <i class=\"mr-2 fa fa-plus\"></i>\n" .
            "                        Novo {$output}\n" .
            "                    </popup-button>\n" .
            "                </div>\n" .
            "            </div>\n" .
            "            <div>\n" .
            "                <datatable\n" .
            "                    id=\"{$componentName}\"\n" .
            "                    :columns=\"columns\"\n" .
            "                    :source=\"source\"\n" .
            "                ></datatable>\n" .
            "            </div>\n" .
            "        </div>\n" .
            "    </LayoutPrincipal>\n" .
            "</template>\n\n" .
            "<script setup>\n" .
            "import { ref, inject } from 'vue';\n" .
            "import Datatable from '@/Components/datatable/Datatable.vue';\n" .
            "import LayoutPrincipal from '@/Layouts/LayoutPrincipal.vue';\n" .
            "import PopupButton from '@/Components/PopupButton.vue';\n" .
            "import { useToast } from 'vue-toastification';\n\n" .
            "const toast = useToast();\n" .
            "const events = inject('events');\n" .
            "const source = ref('/admin/{$componentName}/list');\n\n" .
            "const columns = ref([\n" .
            $gridFields .
            "    {\n" .
            "        name: 'id',\n" .
            "        title: 'Ações',\n" .
            "        width: '10%',\n" .
            "        nowrap: true,\n" .
            "        contentClass: 'text-center',\n" .
            "        headerClass: 'text-center',\n" .
            "        template: 'dropdown',\n" .
            "        formatter: (val, row) => [\n" .
            "            {\n" .
            "                type: 'modal',\n" .
            "                icon: 'fa-edit',\n" .
            "                dataSize: 'xl',\n" .
            "                dataComponent: '{$className}Form',\n" .
            "                dataTitle: 'Editar {$output}',\n" .
            "                dataJson: { id: row.id },\n" .
            "                text: 'Editar'\n" .
            "            },\n" .
            "            {\n" .
            "                type: 'delete',\n" .
            "                icon: 'fa-trash',\n" .
            "                text: 'Remover',\n" .
            "                deleteUrl: `/admin/{$componentName}/\${row.id}`,\n" .
            "                dataTitle: 'Confirmação de Remoção',\n" .
            "                dataMessage: 'Você deseja realmente excluir este registro?'\n" .
            "            }\n" .
            "        ]\n" .
            "    }\n" .
            "]);\n" .
            "</script>\n";
    }

    private function getFormContent($moduleName, $formColumns, $tableName): string
    {
        $output = preg_replace('/([A-Z])/', ' $1', $moduleName);
        $className = ucfirst($moduleName);
        $componentName = str_replace('_', '-', $tableName);

        $formFields = "";
        $formInitialValues = "";

        foreach ($formColumns as $column) {
            $title = ucwords(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $column)));
            $formFields .= "            <div class=\"mb-4\">\n" .
                "                <InputLabel for=\"{$column}\" value=\"" . ucfirst($title) . "\" class=\"required\"/>\n" .
                "                <TextInput\n" .
                "                    id=\"{$column}\"\n" .
                "                    class=\"w-full\"\n" .
                "                    v-model=\"form.{$column}\"\n" .
                "                    :disabled=\"readOnly\"\n" .
                "                />\n" .
                "                <InputError :message=\"errors.{$column}\"/>\n" .
                "            </div>\n\n";

            $formInitialValues .= "    {$column}: '',\n";
        }

        return "<template>\n" .
            "    <div class=\"m-2\" v-if=\"ready\">\n" .
            "        <form @submit.prevent=\"submit\">\n" .
            $formFields .
            "            <div class=\"w-full pt-4 mt-4 border-t border-gray-200\">\n" .
            "                <div class=\"flex justify-center\" v-if=\"readOnly\">\n" .
            "                    <button\n" .
            "                        type=\"button\"\n" .
            "                        class=\"px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700\"\n" .
            "                        @click=\"close\"\n" .
            "                    >\n" .
            "                        <i class=\"mr-1 fa fa-close\"></i> Sair\n" .
            "                    </button>\n" .
            "                </div>\n" .
            "                <div class=\"flex justify-center space-x-2\" v-if=\"!readOnly\">\n" .
            "                    <button\n" .
            "                        type=\"submit\"\n" .
            "                        class=\"px-4 py-2 text-white rounded-md bg-blue-500 hover:bg-blue-500-hover\"\n" .
            "                        :disabled=\"processing\"\n" .
            "                    >\n" .
            "                        <i v-if=\"!processing\" class=\"mr-1 fa fa-check\"></i>\n" .
            "                        <i v-else class=\"mr-1 fa fa-spinner fa-spin\"></i>\n" .
            "                        {{ processing ? 'Salvando...' : 'Salvar' }}\n" .
            "                    </button>\n" .
            "                    <button\n" .
            "                        type=\"button\"\n" .
            "                        class=\"px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700\"\n" .
            "                        @click=\"close\"\n" .
            "                    >\n" .
            "                        <i class=\"mr-1 fa fa-close\"></i> Cancelar\n" .
            "                    </button>\n" .
            "                </div>\n" .
            "            </div>\n" .
            "        </form>\n" .
            "    </div>\n" .
            "</template>\n\n" .
            "<script setup>\n" .
            "import { inject, onMounted, ref } from 'vue';\n" .
            "import InputLabel from '@/Components/InputLabel.vue';\n" .
            "import InputError from '@/Components/InputError.vue';\n" .
            "import TextInput from '@/Components/TextInput.vue';\n" .
            "import { useToast } from 'vue-toastification';\n\n" .
            "const props = defineProps({\n" .
            "    data: {\n" .
            "        type: Object,\n" .
            "        default: null,\n" .
            "        required: false\n" .
            "    }\n" .
            "});\n\n" .
            "const emit = defineEmits(['close']);\n" .
            "const acao = ref('/admin/{$componentName}/');\n" .
            "const events = inject('events');\n" .
            "const toast = useToast();\n" .
            "const errors = ref({});\n" .
            "const processing = ref(false);\n" .
            "const ready = ref(false);\n" .
            "const readOnly = ref(false);\n\n" .
            "const form = ref({\n" .
            $formInitialValues .
            "});\n\n" .
            "function submit() {\n" .
            "    processing.value = true;\n" .
            "    axios.post(acao.value, form.value)\n" .
            "        .then(response => {\n" .
            "            events.emit('table-reload');\n" .
            "            handleSuccess();\n" .
            "            processing.value = false;\n" .
            "        })\n" .
            "        .catch(error => {\n" .
            "            processing.value = false;\n\n" .
            "            if (error.response) {\n" .
            "                const data = error.response.data;\n\n" .
            "                if (data.errors) {\n" .
            "                    errors.value = data.errors;\n" .
            "                }\n" .
            "                const message = data.message || \"Ocorreu um erro ao salvar {$output}.\";\n" .
            "                handleError(message);\n" .
            "            } else {\n" .
            "                handleError(\"Erro de conexão com o servidor.\");\n" .
            "            }\n" .
            "        })\n" .
            "        .finally(() => {\n" .
            "            processing.value = false;\n" .
            "        });\n" .
            "}\n\n" .
            "function handleSuccess() {\n" .
            "    if(props.data?.id) {\n" .
            "        toast.success(\"{$output} editado com sucesso!\");\n" .
            "    } else {\n" .
            "        toast.success(\"{$output} criado com sucesso!\");\n" .
            "    }\n" .
            "    close();\n" .
            "}\n\n" .
            "function handleError(msg) {\n" .
            "    toast.error(msg);\n" .
            "}\n\n" .
            "const loadData = async () => {\n" .
            "    try {\n" .
            "        const response = await axios.get(`/admin/{$componentName}/\${props.data.id}`);\n" .
            "        Object.keys(form.value).forEach(key => {\n" .
            "            if (response.data[key] !== undefined) {\n" .
            "                form.value[key] = response.data[key];\n" .
            "            }\n" .
            "        });\n" .
            "        readOnly.value = Boolean(props.data.readOnly);\n" .
            "    } catch (err) {\n" .
            "        console.error('Error loading data:', err);\n" .
            "        toast.error('Não foi possível recuperar os dados do {$output}.');\n" .
            "    } finally {\n" .
            "        ready.value = true;\n" .
            "    }\n" .
            "}\n\n" .
            "const close = () => {\n" .
            "    events.emit('popup-close', true);\n" .
            "}\n\n" .
            "onMounted(async () => {\n" .
            "    if (props.data?.id) {\n" .
            "        acao.value = `/admin/{$componentName}/\${props.data.id}`;\n" .
            "        await loadData();\n" .
            "    } else {\n" .
            "        ready.value = true;\n" .
            "    }\n" .
            "});\n" .
            "</script>\n";
    }

    private function addRoute(string $moduleName, string $tableName): void
    {
        $componentName = str_replace('_', '-', $tableName);
        $importStatement = "use App\\Http\\Controllers\\Admin\\" . ucfirst($moduleName) . "Controller;";
        $routeContent =
            "Route::group(['prefix' => 'admin/{$componentName}'], function () {\n" .
            "    Route::get('/', [" . ucfirst($moduleName) . "Controller::class, 'index'])->name('admin.{$tableName}.index');\n" .
            "    Route::get('/list', [" . ucfirst($moduleName) . "Controller::class, 'list'])->name('admin.{$tableName}.list');\n" .
            "    Route::get('/{id}', [" . ucfirst($moduleName) . "Controller::class, 'edit'])->name('admin.{$tableName}.edit');\n" .
            "    Route::post('/', [" . ucfirst($moduleName) . "Controller::class, 'create'])->name('admin.{$tableName}.create');\n" .
            "    Route::post('/{id}', [" . ucfirst($moduleName) . "Controller::class, 'update'])->name('admin.{$tableName}.update');\n" .
            "    Route::delete('/{id}', [" . ucfirst($moduleName) . "Controller::class, 'delete'])->name('admin.{$tableName}.delete');\n" .
            "});";

        $webRoutePath = app()->basePath('routes/web.php');

        if (File::exists($webRoutePath)) {
            $fileContent = File::get($webRoutePath);

            if (!str_contains($fileContent, $importStatement)) {
                $fileContent = preg_replace(
                    '/^<\?php\s*/',
                    "<?php\n\n" . $importStatement . "\n",
                    $fileContent
                );
            }

            if (!str_contains($fileContent, $routeContent)) {
                $fileContent .= "\n" . $routeContent;
            } else {
                $this->warn("As rotas do módulo '{$moduleName}' já existem no arquivo web.php.");
            }

            File::put($webRoutePath, $fileContent);
            $this->info("Rotas do módulo '{$moduleName}' adicionadas ao arquivo web.php com sucesso.");
        } else {
            $this->error("O arquivo web.php não foi encontrado em: {$webRoutePath}");
        }
    }

    private function addBindings(string $moduleName): void
    {
        $moduleNameCaps = ucfirst($moduleName);
        $contractNamespace = "App\\Databases\\Contracts\\" . ucfirst($moduleName) . "Contract";
        $repositoryNamespace = "App\\Databases\\Repositories\\" . ucfirst($moduleName) . "Repository";
        $bindingLine = "app()->bind({$moduleNameCaps}Contract::class, {$moduleNameCaps}Repository::class);";

        $providerPath = app()->basePath('app/Providers/AppServiceProvider.php');

        if (File::exists($providerPath)) {
            $fileContent = File::get($providerPath);

            if (!str_contains($fileContent, $contractNamespace)) {
                $fileContent = preg_replace(
                    '/(namespace App\\\\Providers;[\s\S]*?use [^\n]+;)/',
                    "$1\nuse {$contractNamespace};\nuse {$repositoryNamespace};",
                    $fileContent
                );
            }

            if (!str_contains($fileContent, $bindingLine)) {
                $fileContent = preg_replace(
                    '/(public function boot\(\): void\n\s*{\n)/',
                    "$1        {$bindingLine}\n",
                    $fileContent
                );
            }

            File::put($providerPath, $fileContent);
            $this->info("Bindings do módulo '{$moduleName}' adicionados ao AppServiceProvider com sucesso.");
        } else {
            $this->error("O arquivo AppServiceProvider.php não foi encontrado em: {$providerPath}");
        }
    }
}