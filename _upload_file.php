<?php
/**
 * Created by PhpStorm.
 * User: dennys
 * Date: 11/04/18
 * Time: 13:56
 */

// Upload de arquivos
// $destino está em cima da chamada deste include.
// $ext_perm está em cima da chamada deste include.

if (is_uploaded_file($_FILES['arquivo']['tmp_name'][0])) {
    $qtdArquivos = count($_FILES['arquivo']['tmp_name']);
    

    @mkdir($destino, 0777, true);

    for($i=0;$i<$qtdArquivos;$i++) {

        $ext = pathinfo($_FILES['arquivo']['name'][$i], PATHINFO_EXTENSION);
        if(in_array($ext, $ext_perm)) {
            $nome = pathinfo($_FILES['arquivo']['name'][$i], PATHINFO_BASENAME);
            $nome = str_replace(' ', '-', $nome);
            $source = $_FILES['arquivo']['tmp_name'][$i];
            move_uploaded_file($source, $destino . $nome);

        } else {
            $erro[] = 'Não é permitido enviar arquivo com extensão ' . strtoupper($ext);
        }
    }
}