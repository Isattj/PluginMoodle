<h1>Tutorial de implementação do plugin em uma ferramenta externa e seu funcionamento</h1>
O Código presente neste repositório possui um plugin genérico que pode ser instalado e utilizado pelo Moodle para obter informações sobre o curso em que a plataforma está conectada, além de fornecer dados sobre os usuários e grades da mesma.

Para realizar a configuração do plugin dentro do Moodle siga os passos abaixo:
  1. Copie a pasta "myplugin", presente neste repositório, e cole no seu diretório do Moodle no caminho "moodle/local/";
  2. Deixe o arquivo "test_tool.php" fora do diretório do Moodle, em algum outro caminho;
  3. No terminal, vá até o diretório onde está o arquivo "test_tool.php" e rode o arquivo por meio do comando: php -S localhost:8000
  4. Abra o seu Moodle e faça a isntalação desse plugin, adicionando o caminho onde o arquivo está rodando, uma chave e o segredo que quiser:
     
     <img width="1330" height="494" alt="image" src="https://github.com/user-attachments/assets/0b46916b-5e19-48d7-8849-86bd19c95ce2" />

     
Após a instalação e configuração do plugin no Moodle já é possível visualizar o formato que os dados são obtidos pelo plugin. É necessário somente acessar o link: [http://127.0.0.1/moodle/local/myplugin/export.php?courseid=COURSEID](http://127.0.0.1/moodle/local/myplugin/export.php?courseid=COURSEID), onde COURSEID é substituido pelo id do curso desejado.

Para conectar a ferramenta "test_tool.php" como LTI no nosso Moodle devemos seguir esses passos:
  1. No Moodle vá em Site Administration > Plugins > External Tools > Manage tools > configure a tool manually;
  2. Preencha os campos assim como na imagem abaixo:
  <img width="1642" height="782" alt="image" src="https://github.com/user-attachments/assets/1c032496-4d74-494a-b895-7525d51792de" />
  
  <ul>
    <li><b>Tool name:</b> <i>Coloque o nome que desejar</i></li>
    <li><b>LTI version:</b> LTI 1.0/1.1</li>
    <li><b>Consumer key:</b> <i>A mesma que foi colocada como chave da API na configuração do plugin</i></li>
    <li><b>Share key:</b> <i>O mesmo que foi colocado como segredo da API na configuração do plugin</i></li>
    <li><b>Tool configuration usage:</b> Show in activity chooser and as a preconfigured tool</li>
    <li><b>Default launch container:</b> New window</li>
  </ul>


  3. Após isso adicione a atividade em algum curso em seu Moodle e abra a atividade.
Ao abrir a atividade você será redirecionado para uma nova janela com todas as informações que foram passadas por meio da LTI pelo método POST.
<img width="1047" height="645" alt="image" src="https://github.com/user-attachments/assets/40d97361-9d8f-4b58-bb15-0c8aadf89c2e" />



E logo em abaixo podemos ver as informaçações do curso em que a ferramenta está configurada e que foram coletadas e enviadas para a ferramenta externa por meio do plugin.
<img width="918" height="911" alt="image" src="https://github.com/user-attachments/assets/09cb1fc0-0dbc-4f81-82fe-895aca843cdd" />

