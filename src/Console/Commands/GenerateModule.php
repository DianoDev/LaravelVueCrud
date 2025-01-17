<?php


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria a estrutura de arquivos para um módulo';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $moduleName = $this->argument('name');
        $moduleNameCaps = ucfirst($moduleName);

        // Perguntar ao usuário o nome da tabela no banco
        $tableName = $this->ask("Qual é o nome da tabela no banco de dados?");

        // Obter as colunas da tabela
        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

        if (empty($columns)) {
            $this->error("Nenhuma coluna encontrada na tabela '{$tableName}'. Verifique o nome e tente novamente.");
            return 1;
        }

        // Permitir que o usuário escolha as colunas para $form e $grid
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
            "app/Databases/Models/{$moduleNameCaps}.php" => $this->getModelContent($moduleName),
            "app/Databases/Repositories/{$moduleNameCaps}Repository.php" => $this->getRepositoryContent($moduleName, $formColumns),
            "app/Http/Controllers/$moduleNameCaps/{$moduleNameCaps}Controller.php" => $this->getControllerContent($moduleName, $formColumns),
            "app/Http/Requests/{$moduleNameCaps}Request.php" => $this->getRequestContent($moduleName),
            "resources/views/{$moduleName}/index.blade.php" => $this->getViewContent($moduleName),
            "resources/js/components/{$moduleName}/{$moduleNameCaps}Grid.vue" => $this->getGridContent($moduleName, $gridColumns),
            "resources/js/components/{$moduleName}/{$moduleNameCaps}Form.vue" => $this->getFormContent($moduleName, $formColumns),
        ];

        foreach ($paths as $path => $content) {
            $this->createFile($path, $content);
        }

        $this->addRoute($moduleName);
        $this->addBindings($moduleName);

        $this->info("Módulo '{$moduleName}' criado com sucesso!");
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

    private function getModelContent($moduleName)
    {
        $className = ucfirst($moduleName); // Primeira letra maiúscula para o nome da classe
        $tableName = strtolower($moduleName); // Tudo em minúsculo para o nome da tabela
        $sequenceName = "{$tableName}_id_seq"; // Sequência no formato `nomeTabela_id_seq`

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
        $className = ucfirst($moduleName); // Primeira letra maiúscula
        $modelVariable = lcfirst($moduleName); // Primeira letra minúscula para o uso como variável
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
            "    /**\n" .
            "     * Constructor\n" .
            "     * @param {$className} \${$modelVariable}\n" .
            "     */\n" .
            "    public function __construct(private {$className} \${$modelVariable})\n" .
            "    {\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Buscar registro {$className} por Id\n" .
            "     * @param int \$id\n" .
            "     * @return Model\n" .
            "     */\n" .
            "    public function getById(int \$id): Model\n" .
            "    {\n" .
            "        return {$className}::query()\n" .
            "            ->where('id', '=', \$id)\n" .
            "            ->firstOrFail();\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Busca todos registros de {$className}\n" .
            "     * @return Collection\n" .
            "     */\n" .
            "    public function getAll(): Collection\n" .
            "    {\n" .
            "        return {$className}::query()->get();\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Pagina com filtros as {$className}\n" .
            "     * @param array \$pagination\n" .
            "     * @param array \$columns\n" .
            "     * @return LengthAwarePaginator\n" .
            "     */\n" .
            "    public function paginate(array \$pagination = [], array \$columns = ['*']): LengthAwarePaginator\n" .
            "    {\n" .
            "        \$query = {$className}::query();\n\n" .
            $filterConditions . "\n\n" .
            "        \$query->orderBy(\$pagination['sort'] ?? 'nome', \$pagination['sort_direction'] ?? 'asc');\n" .
            "        return \$query->paginate(\$pagination['per_page'] ?? 10, \$columns, 'page', \$pagination['current_page'] ?? 1);\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Cria um novo registro de {$className}\n" .
            "     * @param array \$params\n" .
            "     * @param bool \$autoCommit\n" .
            "     * @return bool\n" .
            "     * @throws Exception\n" .
            "     */\n" .
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
            "    /**\n" .
            "     * Atualiza um registro existente de {$className}\n" .
            "     * @param int \$id\n" .
            "     * @param array \$params\n" .
            "     * @param bool \$autoCommit\n" .
            "     * @return bool\n" .
            "     * @throws Exception\n" .
            "     */\n" .
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
            "    /**\n" .
            "     * Deleta {$className}\n" .
            "     * @param int \$id\n" .
            "     * @param bool \$autoCommit\n" .
            "     * @return bool\n" .
            "     * @throws Exception\n" .
            "     */\n" .
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


    function getControllerContent(string $modulo, $formContent): string
    {
        $className = ucfirst($modulo); // Converte para PascalCase (ex.: pessoa -> Pessoa)
        $variableName = lcfirst($modulo); // Converte para camelCase (ex.: Pessoa -> pessoa)
        $filterOptions = implode(",\n", array_map(function ($field) {
            return "            '{$field}' => [\n                'type' => 'text',\n            ]";
        }, $formContent));

        return "<?php\n" .
            "namespace App\\Http\\Controllers\\{$className};\n\n" .
            "use Illuminate\\Http\\Request;\n" .
            "use App\\Http\\Controllers\\Controller;\n" .
            "use Illuminate\\View\\View;\n" .
            "use Illuminate\\Http\\JsonResponse;\n" .
            "use App\\Databases\\Contracts\\{$className}Contract;\n" .
            "use App\\Http\\Requests\\{$className}Request;\n\n" .
            "class {$className}Controller extends Controller\n" .
            "{\n" .
            "    /**\n" .
            "     * Constructor\n" .
            "     * @param {$className}Contract \${$variableName}Repository\n" .
            "     */\n" .
            "    public function __construct(private readonly {$className}Contract \${$variableName}Repository)\n" .
            "    {\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Página inicial do {$className}\n" .
            "     * @return View\n" .
            "     */\n" .
            "    public function index(): View\n" .
            "    {\n" .
            "        return view('{$modulo}.index');\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Lista registros paginados e filtrados de {$className}\n" .
            "     * @param Request \$request\n" .
            "     * @return JsonResponse\n" .
            "     */\n" .
            "     public function list(Request \$request): JsonResponse\n" .
            "    {\n" .
            "        \$dados = \$this->{$variableName}Repository->paginate(\$request->all())->toArray();\n" .
            "        \$dados['filter_options'] = [\n" .
            $filterOptions . "\n" .
            "        ];\n" .
            "        return response()->json(\$dados);\n" .
            "    }\n" .
            "    public function create({$className}Request \$request): JsonResponse\n" .
            "    {\n" .
            "        \$params = \$request->except('_token');\n" .
            "        \$this->{$variableName}Repository->create(\$params);\n" .
            "        return response()->json('success', 201);\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Obtém os detalhes de um registro específico de {$className}\n" .
            "     * @param int \$id\n" .
            "     * @return JsonResponse\n" .
            "     */\n" .
            "    public function edit(int \$id): JsonResponse\n" .
            "    {\n" .
            "        \$registro = \$this->{$variableName}Repository->getById(\$id);\n" .
            "        return response()->json(\$registro);\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Atualiza um registro existente de {$className}\n" .
            "     * @param {$className}Request \$request\n" .
            "     * @param int \$id\n" .
            "     * @return JsonResponse\n" .
            "     */\n" .
            "    public function update({$className}Request \$request, int \$id): JsonResponse\n" .
            "    {\n" .
            "        \$params = \$request->except('_token');\n" .
            "        \$this->{$variableName}Repository->update(\$id, \$params);\n" .
            "        return response()->json(['success', \$params]);\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Exclui um registro específico de {$className}\n" .
            "     * @param int \$id\n" .
            "     * @return JsonResponse\n" .
            "     */\n" .
            "    public function delete(int \$id): JsonResponse\n" .
            "    {\n" .
            "        \$this->{$variableName}Repository->destroy(\$id);\n" .
            "        return response()->json('success');\n" .
            "    }\n" .
            "}\n";
    }

    function getRequestContent(string $modulo): string
    {
        $className = ucfirst($modulo); // Converte para PascalCase (ex.: pessoa -> Pessoa)

        return "<?php\n" .
            "namespace App\\Http\\Requests;\n\n" .
            "use Illuminate\\Foundation\\Http\\FormRequest;\n\n" .
            "class {$className}Request extends FormRequest\n" .
            "{\n" .
            "    /**\n" .
            "     * Regras de validação para o {$className}\n" .
            "     * @return string[][]\n" .
            "     */\n" .
            "    public function rules(): array\n" .
            "    {\n" .
            "        return [\n" .
            "            // Adicione suas regras de validação aqui, como\n" .
            "            // 'nome' => ['required', 'string', 'max:128'],\n" .
            "        ];\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Determina se o usuário está autorizado a fazer essa requisição\n" .
            "     * @return bool\n" .
            "     */\n" .
            "    public function authorize(): bool\n" .
            "    {\n" .
            "        return true;\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Mensagens personalizadas de validação\n" .
            "     * @return string[]\n" .
            "     */\n" .
            "    public function messages(): array\n" .
            "    {\n" .
            "        return [];\n" .
            "    }\n" .
            "}\n";
    }

    function getViewContent(string $modulo): string
    {
        $className = ucfirst($modulo); // Converte para PascalCase (ex.: pessoa -> Pessoa)

        return "<x-layout.principal>\n" .
            "    <div class=\"mt-3\">\n\n" .
            "        <div class=\"d-flex justify-content-between align-items-center mb-3\">\n" .
            "            <h2 class=\"text-primary mb-0\">\n" .
            "                {$className}s\n" .
            "            </h2>\n" .
            "            <div class=\"d-flex\">\n" .
            "                <div class=\"ms-2\">\n" .
            "                    <popup-button id=\"nova-{$modulo}\" title=\"Nova {$className}\"\n" .
            "                                  component=\"{$modulo}-form\" action=\"/{$modulo}/\" size=\"xl\">\n" .
            "                        <i class=\"fa fa-plus\"></i>\n" .
            "                        Nova {$className}\n" .
            "                    </popup-button>\n" .
            "                </div>\n" .
            "            </div>\n\n" .
            "        </div>\n\n" .
            "        <div class=\"card\">\n" .
            "            <div class=\"card-body\">\n" .
            "                <{$modulo}-grid>\n" .
            "                </{$modulo}-grid>\n" .
            "            </div>\n" .
            "        </div>\n" .
            "    </div>\n" .
            "</x-layout.principal>\n";
    }

    private function getGridContent($moduleName, $gridColumns): string
    {
        $gridFields = "";
        foreach ($gridColumns as $column) {
            $gridFields .= "    {name: '{$column}', title: '" . ucfirst($column) . "', width: '20%', sort: '{$column}', nowrap: true},\n";
        }

        return
            "<template>\n" .
            "    <div>\n" .
            "        <datatable id=\"" . strtolower($moduleName) . "\" :columns=\"columns\" @delete=\"confirmRemove\" :source=\"source\"></datatable>\n" .
            "    </div>\n" .
            "</template>\n\n" .
            "<script setup>\n" .
            "import {ref, inject} from 'vue';\n\n" .
            "const events = inject('events');\n" .
            "const source = '/" . strtolower($moduleName) . "/list';\n" .
            "const columns = ref([\n" .
            $gridFields .
            "    {\n" .
            "        name: 'id',\n" .
            "        title: 'Ação',\n" .
            "        width: '9%',\n" .
            "        nowrap: true,\n" .
            "        contentClass: 'text-center',\n" .
            "        formatter: (value, row) => {\n" .
            "            let output = \"\";\n" .
            "            output += `<a href=\"javascript:;\" data-json='{\"id\": \"\${value}\"}' data-tooltip=\"Editar\" data-action=\"popup\" data-size=\"xl\" data-component=\"" . strtolower($moduleName) . "-form\" data-title=\"Editar " . ucfirst($moduleName) . "\" class=\" mx-1 action text-align-center tooltip tooltip--top\"><i class=\"fa fa-pencil\"></i></a>`;\n" .
            "            output += `<a href=\"javascript:;\" data-json='{\"id\": \"\${value}\"}' data-tooltip=\"Remover\" data-action=\"delete\" class=\"action mx-0 action-delete tooltip tooltip--top\"><i class=\"fa fa-trash mx-1\"></i></a>`;\n" .
            "            return output;\n" .
            "        }\n" .
            "    }\n" .
            "]);\n\n" .
            "const confirmRemove = async (data) => {\n" .
            "    events.emit('loading', true);\n" .
            "    try {\n" .
            "        await axios.delete('/" . strtolower($moduleName) . "/' + data.id);\n" .
            "        events.emit('table-reload');\n" .
            "        events.emit('notification', {\n" .
            "            type: 'success',\n" .
            "            message: '" . ucfirst($moduleName) . " excluído com Sucesso.'\n" .
            "        });\n" .
            "    } catch (err) {\n" .
            "        events.emit('notification', {\n" .
            "            type: 'error',\n" .
            "            message: err.response?.data?.message || 'Não foi possível excluir o registro.'\n" .
            "        });\n" .
            "    } finally {\n" .
            "        events.emit('loading', false);\n" .
            "    }\n" .
            "}\n\n" .
            "</script>";
    }


    private function getFormContent($moduleName, $formColumns): string
    {
        $formFields = "";
        foreach ($formColumns as $column) {
            $formFields .= "<div class=\"col-lg-12 col-md-12 mb-3\">\n" .
                "    <label for=\"{$column}\" class=\"form-label required\">" . ucfirst($column) . "</label>\n" .
                "    <input v-model=\"info.{$column}\" required type=\"text\" name=\"{$column}\" id=\"{$column}\" class=\"form-control\"\n" .
                "           :disabled=\"readOnly\"/>\n" .
                "</div>\n";
        }

        return
            "<template>\n" .
            "    <div class=\"m-2\" v-if=\"ready\">\n" .
            "        <form id=\"frm\" name=\"frm\" data-method=\"post\" :action=\"acao\">\n" .
            "            <form-error></form-error>\n" .
            $formFields .
            "            <div class=\"row border-top pt-4\">\n" .
            "                <div class=\"col-12 d-flex justify-content-center align-items-center\" v-if=\"readOnly\">\n" .
            "                    <button type=\"button\" class=\"btn btn-danger text-white\" @click=\"close\" aria-label=\"Close\">\n" .
            "                        <i class=\"fa fa-close\"></i> Sair\n" .
            "                    </button>\n" .
            "                </div>\n" .
            "                <div class=\"col-12 text-center\" v-if=\"!readOnly\">\n" .
            "                    <submit-rest label=\"Salvar\"></submit-rest>\n" .
            "                    &nbsp;\n" .
            "                    <button type=\"button\" class=\"btn btn-danger text-white\" @click=\"close\" aria-label=\"Close\">\n" .
            "                        <i class=\"fa fa-close\"></i> Cancelar\n" .
            "                    </button>\n" .
            "                </div>\n" .
            "            </div>\n" .
            "        </form>\n" .
            "    </div>\n" .
            "</template>\n\n" .
            "<script>\n" .
            "import {inject, onMounted, ref, computed} from 'vue';\n\n" .
            "export default {\n" .
            "    setup(props, {emit}) {\n" .
            "        const events = inject('events');\n" .
            "        const info = ref({});\n" .
            "        const ready = ref(false);\n" .
            "        const acao = ref('/" . strtolower($moduleName) . "/');\n" .
            "        const readOnly = ref(false);\n\n" .
            "        const loadData = async () => {\n" .
            "            try {\n" .
            "                acao.value = '/" . strtolower($moduleName) . "/';\n" .
            "               const response = await axios.get(acao.value + props.data.id);\n" .
            "                acao.value += props.data.id;\n" .
            "                info.value = response.data;\n" .
            "                readOnly.value = Boolean(props.data.readOnly);\n" .
            "            } catch (err) {\n" .
            "                emit('notification', {\n" .
            "                    type: 'error',\n" .
            "                    message: 'Não foi possível recuperar os dados do " . strtolower($moduleName) . ".',\n" .
            "                });\n" .
            "            }\n" .
            "            ready.value = true;\n" .
            "        }\n\n" .
            "        const close = () => {\n" .
            "            events.emit('popup-close', true);\n" .
            "        }\n\n" .
            "        onMounted(async () => {\n" .
            "            events.off(\"form-submitted\");\n" .
            "            events.on(\"form-submitted\", (sucesso) => {\n" .
            "                if (sucesso) {\n" .
            "                    events.emit('table-reload', true);\n" .
            "                    events.emit('notification', {\n" .
            "                        type: 'success',\n" .
            "                        message: '" . ucfirst($moduleName) . " salvo com Sucesso!'\n" .
            "                    });\n" .
            "                    emit('close', true);\n" .
            "                }\n" .
            "            });\n" .
            "            if (props.data) {\n" .
            "                await loadData();\n" .
            "            } else {\n" .
            "                ready.value = true;\n" .
            "            }\n" .
            "        });\n\n" .
            "        return {\n" .
            "            info,\n" .
            "            ready,\n" .
            "            acao,\n" .
            "            readOnly,\n" .
            "            close,\n" .
            "        }\n\n" .
            "    },\n\n" .
            "    props: {\n" .
            "        data: {default: null, required: true},\n" .
            "    }\n\n" .
            "}\n" .
            "</script>";
    }


    private function addRoute(string $moduleName): void
    {
        $importStatement = "use App\\Http\\Controllers\\" . ucfirst($moduleName) . "\\" . ucfirst($moduleName) . "Controller;";
        $routeContent =
            "Route::group(['prefix' => '" . strtolower($moduleName) . "'], function () {\n" .
            "    Route::get('/', [" . ucfirst($moduleName) . "Controller::class, 'index'])->name('" . strtolower($moduleName) . ".index');\n" .
            "    Route::get('/list', [" . ucfirst($moduleName) . "Controller::class, 'list'])->name('" . strtolower($moduleName) . ".list');\n" .
            "    Route::get('/{id}', [" . ucfirst($moduleName) . "Controller::class, 'edit'])->name('" . strtolower($moduleName) . ".edit');\n" .
            "    Route::post('/', [" . ucfirst($moduleName) . "Controller::class, 'create'])->name('" . strtolower($moduleName) . ".create');\n" .
            "    Route::post('/{id}', [" . ucfirst($moduleName) . "Controller::class, 'update'])->name('" . strtolower($moduleName) . ".update');\n" .
            "    Route::delete('/{id}', [" . ucfirst($moduleName) . "Controller::class, 'delete'])->name('" . strtolower($moduleName) . ".delete');\n" .
            "});";

        $webRoutePath = \App\Console\Commands\base_path('routes/web.php');

        if (File::exists($webRoutePath)) {
            $fileContent = File::get($webRoutePath);

            // Adiciona a importação, se ainda não existir
            if (!str_contains($fileContent, $importStatement)) {
                $fileContent = preg_replace(
                    '/^<\?php\s*/',
                    "<?php\n\n" . $importStatement . "\n",
                    $fileContent
                );
            }

            // Adiciona as rotas, se ainda não existirem
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

        $providerPath = \App\Console\Commands\app_path('Providers/AppServiceProvider.php');

        if (File::exists($providerPath)) {
            $fileContent = File::get($providerPath);

            // Adiciona a importação, se necessário
            if (!str_contains($fileContent, $contractNamespace)) {
                $fileContent = preg_replace(
                    '/(namespace App\\\\Providers;[\s\S]*?use [^\n]+;)/',
                    "$1\nuse {$contractNamespace};\nuse {$repositoryNamespace};",
                    $fileContent
                );
            }

            // Adiciona o binding, se necessário
            if (!str_contains($fileContent, $bindingLine)) {
                $fileContent = preg_replace(
                    '/(public function boot\(\): void\n\s*{\n)/',
                    "$1        {$bindingLine}\n",
                    $fileContent
                );
            }

            // Salva o arquivo atualizado
            File::put($providerPath, $fileContent);
            $this->info("Bindings do módulo '{$moduleName}' adicionados ao AppServiceProvider com sucesso.");
        } else {
            $this->error("O arquivo AppServiceProvider.php não foi encontrado em: {$providerPath}");
        }
    }


}
