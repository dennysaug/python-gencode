<?php
#uso no terminal/prompt: php -e gera.php
#v1.0.0
#Testes realizados na versão PHP 5.6.36
#Melhorias pendentes:
#-cor para destacar os campos e tabelas nas perguntas

error_reporting(E_ALL);
ini_set('display_errors', 1);

class GeraModulo
{
    public $nome, $tabela, $bArquivo, $enctype, $mudaDB, $novoBanco, $titulo, $tableFrom, $tableUnique, $tableShow, $tabelas;

    /**
     * GeraModulo constructor.
     */
    public function __construct()
    {
        $this->nome = readline('Nome do modulo (Ex.: cadastro_cliente, produto): ');
        $this->diretorio = readline('Nome do diretorio: ');
        $this->tabela = readline("Tabela no DB: {$this->nome}. Deseja Mudar (Y/n): ");
        if ($this->tabela == 'Y') {
            $this->tabela = readline('Informe o nome da tabela no DB: ');
        } else {
            $this->tabela = $this->nome;
        }
        $this->bArquivo = readline('Upload de arquivo (Y/n): ');

        if ($this->bArquivo == 'Y') {
            $this->enctype = 'enctype="multipart/form-data"';
        }

        $this->mudaDB = readline('Deseja usar outro banco (Y/n): ');
        if ($this->mudaDB == 'Y') {
            $this->novoBanco = readline('Nome do outro banco de dados: ');
        }

        $this->titulo = strtoupper(str_replace('_', ' ', $this->nome));

        $this->msgRetorno = array();

        $this->showSelect = array('cadastro' => 'nome_razao_social', 'cadastro_colaborador' => 'nome');
        $this->fk_colaborador = '';

        $this->tableFrom = '';
        $this->tableUnique = 'id';
        $this->tableShow = 'titulo';
        $this->tabelas = array();
    }

    public function describe()
    {
        $jsonFile = 'banco.json';

        $bBanco = 'n';

        if (file_exists($jsonFile)) {
            $banco = json_decode(file_get_contents($jsonFile), true);
            print "\nHOST: {$banco['host']}| USER: {$banco['user']}| PASS: {$banco['pass']}| DB: {$banco['db']}\n\n";
            $bBanco = readline('Deseja mudar as credenciais acima (Y/n): ');
        } else {
            $bBanco = 'Y';
        }

        if ($bBanco == 'Y') {
            print "\n\n";
            $banco['host'] = readline('DB Host: ');
            $banco['user'] = readline('DB User: ');
            $banco['pass'] = readline('DB Pass: ');
            $banco['db'] = readline('DB Name: ');

            $arquivo = fopen($jsonFile, 'w');
            fwrite($arquivo, json_encode($banco));
            fclose($arquivo);
        }

        try {
            $link = mysqli_connect($banco['host'], $banco['user'], $banco['pass'], $banco['db']);
            $result = mysqli_query($link, 'describe ' . $this->tabela);
            $data = mysqli_fetch_all($result);
            return $data;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    public function criarLista($data)
    {
        try {
            $conteudo = file_get_contents('_modelo_listar.php');
        } catch (Exception $e) {
            print $e->getMessage() . "\n";
            die('Arquivo _modelo_listar.php nao encontrado');
        }

        if ($conteudo) {
            $search = array('##nome##', '##tabela##', '##titulo##', '##modulo##', '##diretorio##');
            $replace = array($this->nome, $this->tabela, $this->titulo, $this->nome, $this->diretorio);
            $conteudo = str_replace($search, $replace, $conteudo);
            if ($this->mudaDB == 'Y' && $this->novoBanco) {
                $trocaBanco = '$ndb = \'' . $this->novoBanco . "';\n" . '$action->muda_db($ndb);' . "\n";
                $conteudo = str_replace('##novobanco##', $trocaBanco, $conteudo);
            }

            $th = '';
            $td = '';
            $joinTabela = '';
            $selectJoin = '';

            print "\n\n";
            foreach ($data as $campo) {
                if ($campo[5] != 'auto_increment' && $campo[4] != 'CURRENT_TIMESTAMP') {

                    if (substr($campo[0], 0, 3) == 'fk_') {
                        $tableFrom = readline("Nome da tabela do relacionamento do campo {$campo[0]}: ");
                        $joinTabela .= "\n\t\t\t\t\tjoin " . $tableFrom . " on " . $tableFrom . "." . $this->tableUnique . ' = {$tabela}.' . $campo[0] . "";

                        $tableShow = $this->tableShow;

                        if (isset($this->showSelect[$tableFrom])) {
                            $tableShow = $this->showSelect[$tableFrom];
                        }

                        $selectJoin .= ",\n\t\t\t\t\t" . $tableFrom . "." . $tableShow . " as " . str_replace('fk_', '', $campo[0]);
                        $this->tabelas[] = $tableFrom;
                    }

                    $th .= "\t\t\t\t<th><h3>" . strtoupper(str_replace(array('fk_', '_'), array('', ' '), $campo[0])) . "</h3></th>\n";

                    if ($campo[0] == 'ativo') {
                        $td .= "\t\t\t\t\t" . '<td><?php echo ($r[$x][\'' . $campo[0] . '\']) ? \'sim\' : \'não\'; ?></td>' . "\n";
                    } else {
                        $td .= "\t\t\t\t\t" . '<td><?php echo $r[$x][\'' . str_replace('fk_', '', $campo[0]) . '\']; ?></td>' . "\n";
                    }
                }
            }

            if ($this->bArquivo == 'Y') {
                $th .= "\t\t\t\t<th><h3>ARQUIVO</h3></th>\n";

                $td .= "\t\t\t\t\t<td width=\"70px\" align=\"center\">\n " .
                    "\t\t\t\t\t<?php\n" .
                    "\t\t\t\t\t\t" . '$arquivos = @glob($_SERVER[\'DOCUMENT_ROOT\'] . \'/extranet/files/' . $this->nome . '/\' . $r[$x][\'id\'] . \'/*.*\');' . "\n" .
                    "\t\t\t\t\t\t" . '$totalArquivo = count($arquivos);' . "\n" .
                    "\t\t\t\t\t\t" . 'echo ($totalArquivo > 0) ? "[{$totalArquivo}]" : "--";  ?>' . "\n" .
                    "\t\t\t\t\t</td>";
            }

            $th .= "\t\t\t\t<th><h3>EDITAR</h3></th>";
            try {
                $conteudo = str_replace(array('##th##', '##td##', '##selectJoin##', '##join##'), array($th, $td, $selectJoin, $joinTabela), $conteudo);
                @mkdir($this->nome . '/', 0777, true);
                $arquivo = fopen($this->nome . "/_{$this->nome}_listar.php", 'w');
                fwrite($arquivo, $conteudo);
                fclose($arquivo);
                return "\n@ Lista criada com sucesso {$this->nome}/_{$this->nome}_listar.php";
            } catch (\Exception $e) {
                print "Falha ao criar arquivo _listar.php\n";
                print $e->getMessage();
            }

        }
    }

    public function criarForm($data)
    {
        try {
            $conteudo = file_get_contents('_modelo_form.php');
        } catch (Exception $e) {
            print $e->getMessage() . "\n";
            die('Arquivo _modelo_listar.php nao encontrado');
        }

        $enctype = '';

        $search = array('##nome##', '##tabela##', '##titulo##', '##modulo##', '##diretorio##');
        $replace = array($this->nome, $this->tabela, $this->titulo, $this->nome, $this->diretorio);
        $conteudo = str_replace($search, $replace, $conteudo);
        if ($this->mudaDB == 'Y' && $this->novoBanco) {
            $trocaBanco = '$ndb = \'' . $this->novoBanco . "';\n" . '$action->muda_db($ndb);' . "\n";
            $conteudo = str_replace('##novobanco##', $trocaBanco, $conteudo);
        }

        $tr = '';
        $validacao = '';

        $fk_colaborador = $this->fk_colaborador;

        foreach ($data as $campo) {
            if ($campo[5] != 'auto_increment' && $campo[4] != 'CURRENT_TIMESTAMP') {

                if ($campo[0] == 'fk_colaborador') {
                    $fk_colaborador = '$form[\'fk_colaborador\'] = $user_id;';
                    continue;
                }

                if ($campo[2] == 'NO' && (stristr($campo[1], 'int') || stristr($campo[1], 'char') || stristr($campo[1], 'text') || stristr($campo[1], 'enum') || stristr($campo[1], 'decimal') || stristr($campo[1], 'date'))) {
                    if($campo[0] == 'titulo') {
                        $validacao .= "\t" . 'if(!strlen($form[\'titulo\'])) {' . "\n\t\t" . '$__modulo[\'erro\'][] = \'Informe titulo\';' . "\n\t" . '} else {' .
                                      "\n\t\t" . '$query = "select * from " . $dados[\'tabela\'] . " where titulo = \'" . $action->secure($form[\'titulo\']) . "\'";' .
                                      "\n\t\t\t" . 'if (isset($__modulo[\'id\'])) {' .
                                      "\n\t\t\t\t" . '$query .= " and id <> " . $action->secure($__modulo[\'id\']);' .
                                      "\n\t\t\t" . '}' .
                                      "\n\t\t\t" . '$result = $action->query_db($query);' .
                                      "\n\t\t\t" . 'if ($action->result_quantidade($result) > 0) {' .
                                      "\n\t\t\t\t" . '$__modulo[\'erro\'][] = \'Título já existe. Você precisa escolher um título único\';' .
                                      "\n\t\t\t}\n\t}\n\n";

                    } else {
                        $validacao .= "\t" . 'if(!strlen($form[\'' . $campo[0] . '\'])) ' . "{\n\t\t" . '$__modulo[\'erro\'][] = \'Informe ' . str_replace(array('fk_', '_'), array('', ' '), $campo[0]) . "';\n\t}\n\n";
                    }
                }

                $tr .= "\t\t\t\t<tr align=\"left\">\n";
                $tr .= "\t\t\t\t\t<th><strong>" . strtoupper(str_replace(array('fk_', '_'), array('', ' '), $campo[0])) . "</strong></th>\n";

                #checa tipo

                if (stristr($campo[1], 'int') || stristr($campo[1], 'varchar')) {
                    if (substr($campo[0], 0, 3) == 'fk_') {
                        $tableFrom = $this->tabelas[$campo[0]];

                        $tableShow = $this->tableShow;

                        if (isset($this->showSelect[$tableFrom])) {
                            $tableShow = $this->showSelect[$tableFrom];
                        }

                        $tableOrder = $tableShow;

                        $tr .= "\t\t\t\t\t<td><?php print formrel(\"form[" . $campo[0] . "]\", \"{$tableFrom}\",\"{$this->tableUnique}\", \"{$tableShow}\", \"{$tableShow}\"," . '$form[\'' . $campo[0] . "']); ?></td>\n\t\t\t\t</tr>\n";
                    } else {
                        $tr .= "\t\t\t\t\t<td><?php print formtext(\"form[" . $campo[0] . "]\",\"80\",\"255\"," . '$form[\'' . $campo[0] . '\']);' . "?></td>\n\t\t\t\t</tr>\n";
                    }

                } elseif (stristr($campo[1], 'text')) {
                    $tr .= "\t\t\t\t\t<td><?php print formtextarea(\"form[" . $campo[0] . "]\",\"51\",\"6\"," . '$form[\'' . $campo[0] . '\']); ?></td>' . "\n\t\t\t\t</tr>\n";
                    continue;
                } elseif (stristr($campo[1], 'enum')) {
                    $tr .= "\t\t\t\t\t<td><?php print formyesno(\"form[" . $campo[0] . "]\"," . '$form[\'' . $campo[0] . "']); ?></td>\n\t\t\t\t</tr>\n";
                    continue;
                } elseif (stristr($campo[1], 'date')) {
                    $tr .= "\t\t\t\t\t<td><?php print formdate(\"form[" . $campo[0] . "]\"," . '$form[\'' . $campo[0] . '\']); ' . "?></td>\n\t\t\t\t</tr>\n";
                    continue;
                } elseif (stristr($campo[1], 'decimal')) {
                    $tr .= "\t\t\t\t\t<td><?php print formtext(\"form[" . $campo[0] . "]\",\"80\",\"255\"," . '$form[\'' . $campo[0] . '\']);' . "?></td>\n\t\t\t\t</tr>\n";
                    continue;
                } elseif (stristr($campo[1], 'char')) {
                    $charLength = str(array('char(', '('), array('', ''), $campo[1]);
                    $tr .= "\t\t\t\t\t<td><?php print formtext(\"form[" . $campo[0] . "]\",\"80\",\"{$charLength}\"," . '$form[\'' . $campo[0] . '\']);' . "?></td>\n\t\t\t\t</tr>\n";
                    continue;
                } else {
                    $tr .= "\t\t\t\t\t<td>NAO FOI POSSIVEL DETECTAR O TIPO</td>\n\t\t\t\t</tr>\n";
                    print "\n[!] Nao possivel detectar o tipo do campo: {$campo[1]} --> " . strtoupper($campo[0]);
                    continue;
                }

            }
        }

        if ($this->bArquivo == 'Y') {
            $enctype = 'enctype="multipart/form-data"';
            $tr .= "\t\t\t\t<tr>\n";
            $tr .= "\t\t\t\t\t<th><strong>ARQUIVO</strong></th>\n";
            $tr .= "\t\t\t\t\t<td><input name=\"arquivo[]\" type=\"file\" multiple accept=\"application/pdf,image/*\"/></td>\n\t\t\t\t</tr>\n";

            $tr .= "\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t<ol>\n\t\t\t\t\t\t" . '<?php foreach($lista_arquivos as $arquivo) { ?>' . "" . "\n\t\t\t\t\t\t<li><a href=\"<?php print \$arquivo ?>\" target=\"_blank\"><h3><?php print end(explode('/',\$arquivo)); ?></h3></a></li>\n\t\t\t\t\t\t<?php } ?>\n\t\t\t\t\t</ol>\n\t\t\t\t</td>\n\t\t\t\t</tr>";

            $includeupload = file_get_contents('upload.txt');
            $includeupload = str_replace('##nome##', $this->nome, $includeupload);

            $conteudo = str_replace('##includeupload##', $includeupload, $conteudo);
            $conteudo = str_replace('##enctype##', $enctype, $conteudo);


            copy('_upload_file.php', $this->nome . '/_upload_file.php');
        }

        try {
            $conteudo = str_replace(array('##tr##', '##validacao##', '##fk_colaborador##'), array($tr, $validacao, $fk_colaborador), $conteudo);
            @mkdir($this->nome . '/', 0777, true);
            $arquivo = fopen($this->nome . "/_{$this->nome}_form.php", 'w');
            fwrite($arquivo, $conteudo);
            fclose($arquivo);
            return "\n@ Form criado com sucesso {$this->nome}/_{$this->nome}_form.php\n\n";
        } catch (Exception $e) {
            print "\nFalha ao criar arquivo _form.php\n";
            print $e->getMessage();
        }
    }

}

$oModulo = new GeraModulo();
$data = $oModulo->describe();
$msg1 = $oModulo->criarLista($data);
$msg2 = $oModulo->criarForm($data);

print "{$msg1}{$msg2}";