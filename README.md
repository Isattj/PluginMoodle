<h1>Tutorial de implementação do plugin em uma ferramenta externa</h1>
O Código presente nesse repositório possui a criação de um plugin genérico que pode ser instalado e utilizado pelo Moodle para obter informações sobre os cursos, usuários, grades, etc.

Para realizar o teste do funcionamento siga os passos abaixo:
  1. Copie a pasta "myplugin", presente neste repositório, e cole no seu diretório do Moodle no caminho "moodle/local/";
  2. Deixe o arquivo "test_tool.php" fora do diretório do Moodle, em algum outro caminho;
  3. No terminal vá até o diretório onde está o arquivo "test_tool.php" e rode o arquivo por meio do comando: php -S localhost:8000
  4. Logo em seguida abra o seu Moodle e faça a isntalação desse plugin, adicionando o caminho onde o arquivo está sendo rodado e adicione uma chave um segredo que quiser:
     
     <img width="1234" height="431" alt="image" src="https://github.com/user-attachments/assets/e169b295-c5a3-42bf-9ba0-d663b8befca4" />
     
Após as instalação e configuração do plugin no Moodle já é possível visualizar o formato que os dados são obtidos pelo plugin. É necessário somente acessar o link: http://127.0.0.1/moodle/local/myplugin/export.php
Para conectar a ferramenta "test_tool.php" como LTI no nosso Moodle devemos seguir esses passos:
  1. No Moodle vá em Site Administration > Plugins > External Tools > Manage tools > configure a tool manually;
  2. Preencha os campos que mostram na imagem abaixo, onde a consumer key deve ser igual a chave e o segrego igual ao segredo que foi configurado na instalação do plugin;
     
     <img width="1795" height="643" alt="image" src="https://github.com/user-attachments/assets/9f5706c8-eef5-4e79-8126-908628908765" />
     <img width="906" height="138" alt="image" src="https://github.com/user-attachments/assets/c3aef6c9-f71b-4cec-a152-39bd53f87445" />


  3. Após isso adicione a atividade em algum curso em seu Moodle e abra a atividade.
Após abrir a atividade você será redirecionado para uma nova janela com todas as informações que foram passadas do plugin para a ferramenta LTI.

<img width="1099" height="550" alt="image" src="https://github.com/user-attachments/assets/6d36d455-9339-48f4-bbc0-78b37fe36e5b" />
