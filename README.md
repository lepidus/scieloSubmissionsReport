# Plugin de relatório de submissões 

Este plugin gera uma planilha **csv** com as seguintes informações : 
- Id da submissão
- Título da submissão
- Usuário submissor
- Data da submissão
- Data da decisão
- Dias até a decisão tomada
- Estado da submissão (avaliação, aprovado, rejeitado)
- Estado da publicação do preprint (não enviado para publicação em periódico, enviado, enviado e aceito)
- DOI da publicação do preprint (caso este tenha sido publicado em periódico)
- Moderador de área da submissão
- Moderadores da submissão
- Nome do servidor
- Seção da submissão
- Idioma da submissão
- Autores (contendo seus nomes, países e afiliação)
- Notas da submissão

__Copyright (c) Lepidus Tecnologia__ 

# Primeiros Passos

## Pré-Requisitos

* OPS 3.2.1


## Download do Plugin

Para fazer download do plugin entre na página de Releases
[clicando aqui](https://gitlab.lepidus.com.br/plugins_ojs/relatorioscielo/-/releases), ou vá em `RelatorioScielo > Project Overview > Releases` e confira a versão que deseja instalar.

## Instalação
1. Entre na área de administração do seu site OPS pelo __Painel de Controle__.
2. Navegue até o `Configurações` > `Website` > `Plugins` > `Enviar novo plugin`.
3. Em __Enviar arquivo__ selecione o arquivo __SubmissionReportPlugin.tar.gz__.
4. Clique em __Salvar__ e o plugin estará sendo instalado no seu OPS. 

# Tecnologias Utilizadas

* CSS
* PHP 7.2.24
* Smarty 3.1.32
* MariaDB 10.1.43

# Licença
__Este plugin está licenciado sob a GNU General Public License v2__
