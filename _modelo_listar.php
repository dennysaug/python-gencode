<?php
error_reporting(0);
##novobanco##
//Ativar debug true = sim / false = não
$dados['debug'] = false;
// Tabela de busca básica, caso seja necessário escrever uma query mais complexa deverá ser escrita na query dinâmica
$dados['tabela'] = '##tabela##';

// Título que aparecerá na página
$__modulo['titulo'] = '##titulo##';
// Diretório onde está contido o arquivo
$__modulo['diretorio'] = '##diretorio##';
// O usuário poderá adicionar novas informações?
$__modulo['permadd'] = true;
// Arquivos que chamarão formulários
$__modulo['add'] = '##nome##_form';
$__modulo['edit'] = '##nome##_form';
##modulofile##
// Lista e campos que aparecerão na busca de sql
$__modulo['campos_busca'] = array("id" => "ID");
// Tamanho da janela pop up para adicionar informações:
$__modulo['janelax'] = 750;
$__modulo['janelay'] = 500;
// Deve mostrar a busca? true = sim / false = não
$__modulo['show_busca'] = false;
// Quantidade de colunas na tabela
$__modulo['tinycols'] = 10;
// Qual a coluna que deverá dar o sort (inicia em zero e termina em ($__modulo['tinycols']-1)
$__modulo['tinysort'] = 0;

$tabela = $action->secure($dados['tabela']);

if ( (isset($_REQUEST['t_busca'])) && (isset($_REQUEST['f_busca'])) && (strlen($_REQUEST['busca'])>1) ) {
    if ($_REQUEST['t_busca']=='exatamente') {       $palavra_busca=$action->secure($_REQUEST['busca']); }
    elseif ($_REQUEST['t_busca']=='inicial') {      $palavra_busca=$action->secure($_REQUEST['busca']).'%'; }
    else {                                          $palavra_busca='%'.$action->secure($_REQUEST['busca']).'%'; }
    
    $query = "  select {$tabela}.* ##selectJoin## 
                    from {$tabela} ##join##                      
                    where {$tabela}.".$action->secure($_REQUEST['f_busca'])." like '".$palavra_busca."' limit 5000";
} else {
    $query = "  select {$tabela}.* ##selectJoin## 
                    from {$tabela} ##join##
                    limit 5000";
}
// Execução da query e retorno de resultados
$result=$action->query_db($query);
if ($action->result_quantidade($result)>0) {
    $r=$action->array_db($result);
    $show_tiny = true;
}
?>

<?php
if ($__modulo['show_busca'] == true) {
    echo '<div id="corpo">' . formlist($__modulo['campos_busca'], $dados['tabela']) . '</div>';
}
?>

    <h2 style="text-transform:uppercase;"><?php echo $__modulo['titulo']; ?></h2>
<?php if ($show_tiny == true) { ?>
    <div id="tablewrapper">
        <div id="tableheader">
            <div class="search">
                <select id="columns" onchange="sorter.search('query')"></select>
                <input type="text" id="query" onkeyup="sorter.search('query')"/>
            </div>
            <span class="details">
                <div>Entradas <span id="startrecord"></span>-<span id="endrecord"></span> de <span
                            id="totalrecords"></span></div>
                <div><a href="javascript:sorter.reset()">resetar</a></div>
            </span>
        </div>
        <table cellpadding="0" cellspacing="0" border="0" id="table" class="tinytable">
            <thead>
            <tr>
                <th><h3>ID</h3></th>
##th##                            
            </tr>
            </thead>
            <tbody>
            <?php
            for ($x = 0; $x < sizeof($r); $x++) {
            ?>
                <tr>
                    <td>
                        <span style="font-size:1px; display:none"><?php echo str_pad($r[$x]['id'], 10, '0', STR_PAD_LEFT); ?></span><?php echo $r[$x]['id']; ?>
                    </td>
##td##                    
                    <td width="70px" align="center">
                        <a href="#" onClick="window.open('form.php?modulo=<?php echo $__modulo['diretorio'] . ':' . $__modulo['edit']; ?>&act=editar&id=<?php echo $r[$x]['id']; ?>', 'formulario', 'width=<?php echo $__modulo['janelax']; ?>, height=<?php echo $__modulo['janelay']; ?>, location=no, toolbar=no, scrollbars=yes, resizable=yes, top=100, left=100');return false;">EDITAR</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <div id="tablefooter">
            <div id="tablenav">
                <div>
                    <img src="js/tinytable/images/first.gif" width="16" height="16" alt="First Page"
                         onclick="sorter.move(-1,true)"/>
                    <img src="js/tinytable/images/previous.gif" width="16" height="16" alt="First Page"
                         onclick="sorter.move(-1)"/>
                    <img src="js/tinytable/images/next.gif" width="16" height="16" alt="First Page"
                         onclick="sorter.move(1)"/>
                    <img src="js/tinytable/images/last.gif" width="16" height="16" alt="Last Page"
                         onclick="sorter.move(1,true)"/>
                </div>
                <div>
                    <select id="pagedropdown"></select>
                </div>
                <div>
                    <a href="javascript:sorter.showall()">ver tudo</a>
                </div>
            </div>
            <div id="tablelocation">
                <div>
                    <span><strong>P&Aacute;GINAS: </strong></span>
                    <select onchange="sorter.size(this.value)">
                        <option value="10" selected="selected">10</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="page">Mostrando <span id="currentpage"></span> de <span id="totalpages"></span></div>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="js/tinytable/script.js"></script>
    <script type="text/javascript">
        var sorter = new TINY.table.sorter('sorter', 'table', {
            headclass: 'head',
            ascclass: 'asc',
            descclass: 'desc',
            evenclass: 'evenrow',
            oddclass: 'oddrow',
            evenselclass: 'evenselected',
            oddselclass: 'oddselected',
            paginate: true,
            size:<?php echo $__modulo['tinycols']; ?>,
            colddid: 'columns',
            currentid: 'currentpage',
            totalid: 'totalpages',
            startingrecid: 'startrecord',
            endingrecid: 'endrecord',
            totalrecid: 'totalrecords',
            hoverid: 'selectedrow',
            pageddid: 'pagedropdown',
            navid: 'tablenav',
            sortcolumn:<?php echo $__modulo['tinysort']; ?>,
            sortdir: 1,
            //sum:[8],
            //avg:[6,7,8,9],
            //columns:[{index:7, format:'%', decimals:1},{index:8, format:'$', decimals:0}],
            init: true
        });
    </script>
    <?php
} else {
    echo '<span class="nenhum_resultado">Nenhum resultado encontrado</span>';
}
?>
<?php if ($__modulo['permadd'] == true) { ?>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="right">&nbsp;</td>
        </tr>
        <tr>
            <td align="right">
                <button class="button"
                        onClick="window.open('form.php?modulo=<?php echo $__modulo['diretorio'] . ':' . $__modulo['add']; ?>&act=adicionar', 'formulario', 'width=<?php echo $__modulo['janelax']; ?>, height=<?php echo $__modulo['janelay']; ?>, location=no, toolbar=no, scrollbars=yes, resizable=yes, top=100, left=100');return false;">
                    <span class="icon">ADICIONAR NOVO ITEM</span></button>
            </td>
        </tr>
    </table>
<?php } ?>
