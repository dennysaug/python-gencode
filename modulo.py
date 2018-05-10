#!/usr/bin/env python
# -*- coding: iso-8859-1 -*-

import MySQLdb
import os


class color:
    PURPLE = '\033[95m'
    CYAN = '\033[96m'
    DARKCYAN = '\033[36m'
    BLUE = '\033[94m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'
    END = '\033[0m'


# Open database connection
db = MySQLdb.connect("localhost", "root", "root", "clickaca_wsb")

# prepare a cursor object using cursor() method
cursor = db.cursor()
msgRetorno = []
enctype = ''
nome = raw_input('Nome do modulo (Ex.: cadastro_cliente, produto): ')
diretorio = raw_input('Nome do diretorio: ')
tabela = raw_input('Nome da tabela no banco de dados: ')
bArquivo = raw_input('Upload de arquivo (Y/n): ')
if bArquivo == 'Y':
    # campoArquivo = raw_input('Nome do campo para upload e arquivo: ')
    enctype = 'enctype="multipart/form-data"'

mudaDB = raw_input('Deseja usar outro banco (Y/n): ')
if mudaDB == 'Y':
    novoBanco = raw_input('Nome do outro banco de dados: ')

titulo = nome.upper().replace('_', ' ')

tableFrom = ''
tableUnique = 'id'
tableShowTitulo = 'titulo'
tabelas = {}

showSelect = {'cadastro' : 'nome_razao_social', 'cadastro_colaborador' : 'nome'}
fk_colaborador = ""


def criarLista(data):
    with open('_modelo_listar.php', 'r') as listar:
        conteudo = listar.read()
        listar.close()
        conteudo = conteudo.replace('##nome##', nome)
        conteudo = conteudo.replace('##tabela##', tabela)
        conteudo = conteudo.replace('##titulo##', titulo)
        conteudo = conteudo.replace('##modulo##', nome)
        conteudo = conteudo.replace('##diretorio##', diretorio)
        if (mudaDB == 'Y' and novoBanco):
            trocaBanco = "$ndb = '" + novoBanco + "';\n$action->muda_db($ndb);\n"
            conteudo = conteudo.replace('##novobanco##', trocaBanco)

        th = ''
        td = ''
        joinTabela = ''
        selectJoin = ''
        for campo in data:
            if campo[5] != 'auto_increment' and campo[4] != 'CURRENT_TIMESTAMP':

                if campo[0][0:3] == 'fk_':
                    tableFrom = raw_input('Nome da tabela do relacionamento do campo ' + color.BLUE + campo[0] + color.END + ': ')
                    # tableUnique = raw_input('Nome do campo na tabela ' + color.CYAN + tableFrom + color.END + ' para ' + color.BOLD + 'relacionar o campo ' + color.END + color.BLUE + campo[0] + color.END + ': ')
                    # tableShow = raw_input('Nome do campo na tabela ' + color.CYAN + tableFrom + color.END + ' para ser ' + color.BOLD + ' mostrado no select: ' + color.END)

                    joinTabela += "\n\t\t\t\t\tjoin " + tableFrom + " on " + tableFrom + "." + tableUnique + " = {$tabela}." + campo[0] + ""
                    
                    if tableFrom in showSelect:
                        tableShow =  showSelect[tableFrom]

                    selectJoin += ",\n\t\t\t\t\t" + tableFrom + "." + tableShow + " as " + campo[0][3:] + ""

                    tabelas.update({campo[0]: tableFrom})

                th += '\t\t\t\t<th><h3>' + campo[0].replace('fk_', '').upper().replace('_', ' ') + '</h3></th>\n'
                if campo[0] == 'ativo':
                    td += '\t\t\t\t\t<td><?php echo ($r[$x][\'' + campo[0] + '\']) ? \'sim\' : \'não\'; ?></td>\n'
                else:
                    td += '\t\t\t\t\t<td><?php echo $r[$x][\'' + campo[0].replace('fk_', '') + '\']; ?></td>\n'

        if bArquivo == 'Y':
            conteudo = conteudo.replace('##modulofile##', '$__modulo[\'file\'] = \'lista_file\';\n')

            th += '\t\t\t\t<th><h3>ARQUIVO</h3></th>\n'

            td += '\t\t\t\t\t<td width="70px" align="center">\n \
                    \t<?php\n \
                    \t$arquivos = @glob($_SERVER[\'DOCUMENT_ROOT\'] . \'/extranet/files/' + nome + '/\' . $r[$x][\'id\'] . \'/*.*\');\n \
                    \t$totalArquivo = count($arquivos);\n \
                    \techo ($totalArquivo > 0) ? "[{$totalArquivo}]" : "--"  ?>\n \
                    </td>'
        th += '\t\t\t\t<th><h3>EDITAR</h3></th>'

        with open('bin/_' + nome + '_listar.php', 'w') as arquivo:
            conteudo = conteudo.replace('##join##', joinTabela)
            conteudo = conteudo.replace('##selectJoin##', selectJoin)
            conteudo = conteudo.replace('##th##', th)
            conteudo = conteudo.replace('##td##', td)
            arquivo.writelines(conteudo)
            arquivo.close()
            msgRetorno.append(
                color.GREEN + '@ Arquivo de lista criado com sucesso: ' + color.BOLD + nome + '_listar.php' + color.END + color.END)


def criarForm(data):    
    with open('_modelo_form.php', 'r') as listar:
        conteudo = listar.read()
        listar.close()
        conteudo = conteudo.replace('##nome##', nome)
        conteudo = conteudo.replace('##tabela##', tabela)
        conteudo = conteudo.replace('##titulo##', titulo)
        conteudo = conteudo.replace('##modulo##', nome.upper().replace('_', ' '))
        conteudo = conteudo.replace('##diretorio##', diretorio)
        if (mudaDB == 'Y' and novoBanco):
            trocaBanco = "$ndb = '" + novoBanco + "';\n$action->muda_db($ndb);\n"
            conteudo = conteudo.replace('##novobanco##', trocaBanco)

        tr = ''
        validacao = ''

        for campo in data:
            if campo[5] != 'auto_increment' and campo[4] != 'CURRENT_TIMESTAMP':

                if campo[0] == 'fk_colaborador':
                    fk_colaborador = "$form['fk_colaborador'] = $user_id;"
                    continue

                if campo[2] == 'NO' and (campo[1].find('int') >= 0 or campo[1].find('char') >= 0 or campo[1].find('text') >= 0 or campo[1].find('enum') >= 0):
                    validacao += '\tif(!strlen($form[\'' + campo[0] + '\'])) {\n\t\t$__modulo[\'erro\'][] = \'Informe ' + campo[0].replace('fk_', '').replace('_', ' ') + '\';\n\t}\n\n'

                tr += '\t\t\t\t<tr align="left">\n'
                tr += '\t\t\t\t\t<th><strong>' + campo[0].replace('fk_', '').replace('_',' ').upper() + '</strong></th>\n'

                # checa o tipo
                if campo[1].find('int') >= 0 or campo[1].find('varchar') >= 0:
                    if campo[0][0:3] == 'fk_':
                        tableFrom = tabelas[campo[0]]

                        tableShow =  tableShowTitulo                   

                        if tableFrom in showSelect:
                            tableShow =  showSelect[tableFrom];

                        tableOrder = tableShow  # raw_input('Nome do campo na tabela ' + tableFrom + 'para ser ordenado: ')
                        tr += '\t\t\t\t\t<td><?php echo formrel("form[' + campo[0] + ']","' + tableFrom + '","' + tableUnique + '", "' + tableShow + '", "' + tableShow + '", $form[\'' + campo[0] + '\']); ?></td>\n\t\t\t\t</tr>\n'
                    else:
                        tr += '\t\t\t\t\t<td><?php echo formtext("form[' + campo[0] + ']","80","255",$form[\'' + campo[0] + '\']); ?></td>\n\t\t\t\t</tr>\n'
                    continue
                elif campo[1].find('text') >= 0:
                    tr += '\t\t\t\t\t<td><?php echo formtextarea("form[' + campo[0] + ']","51","6",$form[\'' + campo[0] + '\']); ?></td>\n\t\t\t\t</tr>\n'
                    continue
                elif campo[1].find('enum') >= 0:
                    tr += '\t\t\t\t\t<td><?php echo formyesno("form[' + campo[0] + ']",$form[\'' + campo[0] + '\']); ?></td>\n\t\t\t\t</tr>\n'
                    continue
                elif campo[1].find('date') >= 0:
                    tr += '\t\t\t\t\t<td><?php echo formdate("form[' + campo[0] + ']",$form[\'' + campo[0] + '\']); ?></td>\n\t\t\t\t</tr>\n'
                    continue
                elif campo[1].find('decimal') >= 0 or campo[1].find('float') >= 0:
                    tr += '\t\t\t\t\t<td><?php echo formtext("form[' + campo[0] + ']","80","255",$form[\'' + campo[0] + '\']); ?></td>\n\t\t\t\t</tr>\n'
                    continue
                elif campo[1].find('char') >= 0:
                    charLength = campo[1].replace('char(', '').replace(')', '')
                    tr += '\t\t\t\t\t<td><?php echo formtext("form[' + campo[0] + ']","80","' + charLength + '",$form[\'' + campo[0] + '\']); ?></td>\n\t\t\t\t</tr>\n'
                    continue

                else:
                    tr += '\t\t\t\t\t<td>NAO FOI POSSIVEL DETECTAR O TIPO</td>\n\t\t\t\t</tr>\n'
                    print color.YELLOW + '[!] Nao possivel detectar o tipo do campo: ' + campo[0].upper() + color.END
                    continue

        if bArquivo == 'Y':
            tr += '\t\t\t\t<tr align="left">\n'
            tr += '\t\t\t\t\t<th><strong>ARQUIVO</strong></th>\n'
            tr += '\t\t\t\t\t<td><input name="arquivo[]" type="file" multiple accept="application/pdf,image/*"/></td>\n\t\t\t\t</tr>\n'

            tr += '\t\t\t\t<tr align="left">\n\
                    <td>\n\
                        <ol>\n\
                            <?php foreach($lista_arquivos as $arquivo) { ?>\n\
                                <li><a href="<?php echo $arquivo ?>" target="_blank"><h3><?php echo end(explode(\'/\',$arquivo)); ?></h3></a></li>\n\
                            <?php } ?>\n\
                        </ol>\n\
                    </td>\n\
                </tr>'

            includeupload = "$destino = $_SERVER['DOCUMENT_ROOT'] . '/extranet/files/" + nome + "/' . $form['id'] . '/';\n$ext_perm = ['jpg', 'bmp', 'gif', 'png', 'pdf'];\ninclude ('_upload_file.php');"
            getArquivos = "\n$arquivos = glob($_SERVER['DOCUMENT_ROOT'] . '/extranet/files/" + nome + "/' . $form['id'] . '/*.*');\nif(count($arquivos)>0) {\n\t$lista_arquivos = [];\n\tforeach ($arquivos as $arquivo) {\n\t\t$lista_arquivos[] = array_pop(explode('/public_html', $arquivo));\n\t}\n}"

            conteudo = conteudo.replace('##includeupload##', includeupload + getArquivos)
            with open('_upload_file.php', 'r') as up_file:
                novo_up_file = open('bin/_upload_file.php', 'w')
                novo_up_file.writelines(up_file.read())
                novo_up_file.close()
                up_file.close()

        conteudo = conteudo.replace('##enctype##', enctype)

        with open('bin/_' + nome + '_form.php', 'w') as arquivo:
            conteudo = conteudo.replace('##tr##', tr)
            conteudo = conteudo.replace('##validacao##', validacao)
            conteudo = conteudo.replace('##fk_colaborador##', fk_colaborador)
            arquivo.writelines(conteudo)
            arquivo.close()
            msgRetorno.append(
                color.GREEN + '@ Arquivo de form criado com sucesso: ' + color.BOLD + nome + '_form.php' + color.END + color.END)


# execute SQL query using execute() method.
cursor.execute("describe " + tabela)

# Fetch a single row using fetchone() method.
data = cursor.fetchall()

criarLista(data)
criarForm(data)

# disconnect from server
db.close()
if bArquivo == 'Y':
    msgRetorno.append(
        color.GREEN + '@ Arquivo de upload criado com sucesso: ' + color.BOLD + '_upload_file.php' + color.END + color.END)
else:
    print ''
    # print 'rm -rf ' + os.getcwd() + '/bin/_upload_file.php'

print '\n'
print "\n".join(msgRetorno)
print '\n'
