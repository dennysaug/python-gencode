<?php
error_reporting(0);
##novobanco##
//Ativar debug true = sim / false = n�o
$dados['debug'] = false;
// Setar tabela que dever� ser atualizada
$dados['tabela'] = '##tabela##';

// T�tulo que aparecer� na p�gina
$__modulo['titulo'] = '##titulo##';
// diretorio:arquivo (sem _ )
$__modulo['arquivo'] = '##diretorio##:##nome##_form';
$__modulo['permadd'] = true;
$__modulo['permedit'] = true;
// Dever� fechar janela automaticamente ao editar / adicionar um resultado?
$__modulo['autoclose'] = true;

// verifica se existe ao definia no envio do formul�rio e seta a mesma dentro de uma vari�vel
if (isset($_REQUEST['act'])) {
    $act = $_REQUEST['act'];
} else {
    $act = 'adicionar';
}

// verifica se existe o id num�rico para atualiza��o ou inser��o, caso contr�rio seta a a��o para adicionar
if (is_numeric($_REQUEST['id'])) {
    $__modulo['id'] = $_REQUEST['id'];
} elseif (is_numeric($form['id'])) {
    $__modulo['id'] = $form['id'];
} else {
}

// in?cio do retorno de informa��es / trabalha erros e retorna mensagem de erro
if (isset($_POST['submit'])) {
    $form = $_POST['form'];
    ##fk_colaborador##

    // Verifica erros
    /*
    if ((valida_email($form['email']) == false)) {
        $__modulo['erro'][] = 'Informe seu e-mail corretamente';
    } else {
        $query = "select * from " . $dados['tabela'] . " where email = '" . $action->secure($form['email']) . "'";
        if (isset($__modulo['id'])) {
            $query .= " and id <> " . $action->secure($__modulo['id']);
        }
        $result = $action->query_db($query);
        if ($action->result_quantidade($result) > 0) {
            $__modulo['erro'][] = 'E-mail j? existe. Voc? precisa escolher um e-mail ?nico';
        }
    }    
    */
    
##validacao##
}

// Adicionando novo registro
if ($act == 'adicionado') {
    if (!isset($__modulo['erro'])) {
        $newact = "editado";
        $__modulo['tituloform'] = 'DADOS ADICIONADOS COM �XITO';
        $form = $action->add_db($dados, $form);
        $__modulo['sucesso'] = 'Informa??es ADICIONADAS - ID: ' . $form['id'];
    } else {
        $__modulo['tituloform'] = 'ADICIONAR DADOS';
        $newact = "adicionado";
        $form = $_POST['form'];
    }
} // Buscando registro no banco e trazendo para o formul�rio
elseif ($act == 'editar') {
    $newact = "editado";
    $__modulo['tituloform'] = 'EDITAR DADOS';
    $query = "select * from " . $action->secure($dados['tabela']) . " where id = '" . $action->secure($__modulo['id']) . "'";
    $result = $action->query_db($query);
    $form = $action->array_db($result);
    $form = $form[0];
} // Editando registro
elseif ($act == 'editado') {
    if (!isset($__modulo['erro'])) {
        $newact = "editado";
        $__modulo['tituloform'] = "DADOS EDITADOS COM �XITO";
        $dados['c_unico'] = 'id';
        $dados['v_unico'] = $__modulo['id'];
        $dados['t_unico'] = 'numerico';
        $form = $action->update_db($dados, $form);
        $__modulo['sucesso'] = 'Informa��es ATUALIZADAS com �xito - ID: ' . $form['id'];
    } else {
        $__modulo['tituloform'] = 'EDITAR DADOS';
        $newact = "editado";
    }
} // Formul�rio limpo, para adicionar novo registro
else {
    $newact = "adicionado";
    $__modulo['tituloform'] = 'ADICIONAR DADOS';
    $form = array();
    $form['ativo'] = '1';
}

##includeupload##


// Retorno erro ou sucesso e gerenciamento de fechamento autom�tico de janela
if (isset($__modulo['erro'])) {
    echo '<span class="erro"><strong>ERRO:</strong><br />';
    for ($x = 0; $x < sizeof($__modulo['erro']); $x++) {
        echo '<li>' . $__modulo['erro'][$x] . '</li>';
    }
    echo '</span>';
} elseif (isset($__modulo['sucesso'])) {
    echo '<span class="acerto"><strong>' . $__modulo['sucesso'] . '</strong></span>';
    if ($__modulo['autoclose'] == true) {
        $fecharjanela = true;
    }
} else {
}


// Formul�rio desnecess�rio quando for fechar janela
if (!isset($fecharjanela)) {
    ?>
    <span class="formulario">
        <h2 style="text-transform:uppercase;"><?php echo $__modulo['titulo']; ?> - <?php echo $__modulo['tituloform']; ?></h2>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" ##enctype##>
            <table width="700" align="left" cellpadding="1" cellspacing="0">
##tr##                                
                <tr align="left">
                    <td>&nbsp;</td>
                    <td><input name="submit" type="submit" id="submit" class="button"
                                            value="<?php if (is_numeric($__modulo['id'])) {
                                                echo 'EDITAR DADOS';
                                            } else {
                                                echo 'ADICIONAR DADOS';
                                            } ?>"/>
                        <input name="act" type="hidden" value="<?php echo $newact; ?>"/>
                        <input name="modulo" type="hidden" value="<?php echo $__modulo['arquivo']; ?>"/>
                        <?php if (is_numeric($__modulo['id'])) {
                            echo '<input name="id" type="hidden" value="' . $action->secure($__modulo['id']) . '">';
                        } ?>
                </tr>
            </table>
        </form>
    </span>
<?php } ?>
