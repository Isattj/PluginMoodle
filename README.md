<h1>Tutorial de implementação do plugin em uma ferramenta externa e seu funcionamento</h1>
O Código presente neste repositório possui um plugin genérico que pode ser instalado e utilizado pelo Moodle para obter informações sobre o curso em que a plataforma está conectada, além de fornecer dados sobre os usuários e grades da mesma.

Para realizar a configuração do plugin dentro do Moodle siga os passos abaixo:
  1. Copie a pasta "myplugin", presente neste repositório, e cole no seu diretório do Moodle no caminho "moodle/local/";
  2. Deixe o arquivo "index.php" fora do diretório do Moodle, em algum outro caminho;
  3. No terminal, vá até o diretório onde está o arquivo "index.php" e rode o arquivo por meio do comando: php -S localhost:8000
  4. Abra o seu Moodle e faça a instalação deste plugin. Não há necessidade de se configurar ou preencher algum campo a mais para realizar sua instalação.

Para conectar a ferramenta "index.php" como LTI no nosso Moodle devemos seguir esses passos:
  1. No Moodle vá em Site Administration > Plugins > External Tools > Manage tools > configure a tool manually;
  2. Preencha os campos assim como na imagem abaixo:
  <img width="1672" height="702" alt="image" src="https://github.com/user-attachments/assets/501c0c28-3554-4c76-8245-84e784e897c0" />

  <ul>
    <li><b>Tool name:</b> <i>Coloque o nome que desejar</i></li>
    <li><b>LTI version:</b> LTI 1.0/1.1</li>
    <li><b>Consumer key:</b> <i>Coloque a consumer key que desejar</i></li>
    <li><b>Shared secret:</b> <i>Coloque a shared secret que desejar</i></li>
    <li><b>Tool configuration usage:</b> Show in activity chooser and as a preconfigured tool</li>
    <li><b>Default launch container:</b> New window</li>
  </ul>

Como a ferramenta externa irá receber as informações por meio da conexão com a LTI, e não acessando uma URL de um endpoint público, iremos utilizar a tecnologia de serviços do Moodle e seus tokens.
Segue abaixo o tutorial da sua implementação:
  1. Após a instalação do plugin no Moodle, vá para Site Administration > Server > Web services > Manage protocols;
  2. Verifique se a opção REST protocol está ativada. Se não estiver, ative-a;
     <img width="1645" height="251" alt="image" src="https://github.com/user-attachments/assets/180b8a03-f78c-4d55-bf34-d4b09038d2b9" />

  4. Vá para Site Administration > Server > Web services > External services;

Link com as informações coletadas até agora e quais são possiveis de obter: https://www.notion.so/Plugin-Moodle-272bf1757bc980ec9dc9fc342288d681?source=copy_link


