<h1>Tutorial de implementação do plugin em uma ferramenta externa e seu funcionamento</h1>
O Código presente neste repositório possui um plugin genérico que pode ser instalado e utilizado pelo Moodle para obter informações sobre o curso em que a plataforma está conectada, além de fornecer dados sobre os usuários e grades da mesma.

<h2>Configuração do plugin dentro do Moodle</h2>
<ol>
    <li>Copie a pasta "myplugin", presente neste repositório, e cole no seu diretório do Moodle no caminho "moodle/local/";</li>
    <li>Deixe o arquivo "index.php" fora do diretório do Moodle, em algum outro caminho;</li>
    <li>No terminal, vá até o diretório onde está o arquivo "index.php" e rode o arquivo por meio do comando: php -S localhost:8000</li>
    <li>Abra o seu Moodle e faça a instalação deste plugin.</li>
</ol>

<h2>Cinfiguração da ferramenta externa por meio da LTI</h2>
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

<h2>Utilização do plugin como serviço</h2>
Como a ferramenta externa irá receber as informações por meio da conexão com a LTI, e não acessando uma URL de um endpoint público, iremos utilizar a tecnologia de serviços do Moodle e seus tokens.
Segue abaixo o tutorial da sua implementação:
<ol>
  <li>Após a instalação do plugin no Moodle, vá para Site Administration > Server > Web services > Manage protocols;</li>
  <li>Verifique se a opção REST protocol está ativada. Se não estiver, ative-a;</li>
     <img width="1645" height="251" alt="image" src="https://github.com/user-attachments/assets/180b8a03-f78c-4d55-bf34-d4b09038d2b9" />

  <li>Vá para Site Administration > Server > Web services > External services;</li>
  <li>Na tabela dos serviços externos verifique se o seu plugin está aparecendo;</li>
  <img width="1706" height="286" alt="image" src="https://github.com/user-attachments/assets/c3e1584e-c555-4214-9d8d-fe26c763fd76" />
  
  <li>Clique na opção "Edit" referente ao seu plugin e verifique se a opção "Enable" está ativada. Se não estiver, ative-a;</li>
  
 <img width="672" height="348" alt="image" src="https://github.com/user-attachments/assets/224f8e2d-cd0d-423b-a95c-b4675c22e720" />

  <li>Em seguida volte a tela com todos os serviços externos e clique na opção "Functions". Verifique se todos os endpoints que você criou no seu plugin estão aparecendo como funções;</li>
  <img width="669" height="410" alt="image" src="https://github.com/user-attachments/assets/a2434939-2a9a-4af1-b8a9-6f153ea9db64" />
</ol>

<h2>Configuração do token para utiliza</h2>
<ol>
  <li>No Moodle, vá até Site Administration > Server > Web services > Manage tokens;</li>
  <li>Clique no botão Create token, para criar um novo token de acesso;</li>
  <li>Preencha os campos solicitados com as seguintes informações:</li>
  <img width="881" height="507" alt="image" src="https://github.com/user-attachments/assets/2e9d8df9-1dc5-46b3-a1e1-149089250bab" />

  <ul>
    <li><b>Name:</b> <i>Coloque um nome da sua preferência</i> (Se nenhum nome for digitado, será gerado um aleatório.)</li>
    <li><b>User:</b> Selecione os usuários que terão acesso a este token</li>
    <li><b>Service: </b> Selecione o plugin "mypluginservice", referenciando que este token terá permissão de utilizar os serviços deste plugin.</li>
    <li><b>IP restriction: </b> <i>Deixe vazio.</i></li>
    <li><b>Valid until: </b> desmarque a opção enable, para garantir que o token não irá perder a validade.</li>
    <li>Após clicar em "Save changes" ELe gerará um token. É muito importante que você copie-o pois ele será utilizado em nosso plugin.</li>
  </ul>
</ol>
Link com as informações coletadas até agora e quais são possiveis de obter: https://www.notion.so/Plugin-Moodle-272bf1757bc980ec9dc9fc342288d681?source=copy_link


