<?php
	// Definir variáveis
	define('BOT_TOKEN', '{TOKEN}');
	define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
	
	// Definir função para pegar os links do cardápio do RU
	function cardapioRU() {
		$arquivo = fopen('https://www.ru.unb.br/index.php/cardapio-refeitorio','r'); // Abre a URL
				
		while(true) {
			$linha = fgets($arquivo);
			if ($linha==null) break;
			$html=$html.$linha;
		}

		fclose($arquivo); // Fecha a URL

		$cod = htmlentities($html); // Salva o código fonte do site do RU
		$cod = str_replace('/images','http://www.ru.unb.br/images',$cod);
		 
		$pattern = '~[a-z]+://\S+.pdf~';
		if ($num_found = preg_match_all($pattern, $cod, $out)){
			$linksfinais[] = $out[0][1];
			$linksfinais[] = $out[0][2];
			$linksfinais[] = $out[0][4];
			$linksfinais[] = $out[0][5];
		}
		
		return $linksfinais; // Links dos 4 cardápios
	}
	
	// Definir função para pegar a última foto do Flickr
	function ftflickr() {
		$arquivo = fopen('https://www.flickr.com/photos/unb_agencia','r'); // Abre a URL
				
		while(true) {
			$linha = fgets($arquivo);
			if ($linha==null) break;
			$html=$html.$linha;
		}
		
		fclose($arquivo); // Fecha a URL
		
		$cod = htmlentities($html); 
		 
		$pattern = '~[a-z]+://\S+.jpg~';
		if ($num_found = preg_match_all($pattern, $cod, $out)){
			$ultima = $out[0][0];
		}

		return $ultima;
	}
	
	// Definir função para mostrar valores em formato de reais (R$)
	function formata_rs ($a) {
		return number_format($a, 2, ',', '.');
	}
	
	// Definir função para tratar a entrada de texto
	function trata_dia ($a) {
		$a = strtolower($a);
		$a = str_replace(" ", "", $a);
		$a = str_replace("á", "a", $a);
		$a_abrev = $a[0].$a[1].$a[2];
		
		if ($a_abrev == 'seg'){
			return 'Seg';
		}
		elseif ($a_abrev == 'ter'){
			return 'Ter';
		}
		elseif ($a_abrev == 'qua'){
			return 'Qua';
		}
		elseif ($a_abrev == 'qui'){
			return 'Qui';
		}
		elseif ($a_abrev == 'sex'){
			return 'Sex';
		}
		elseif ($a_abrev == 'sab'){
			return 'Sab';
		}
		elseif ($a_abrev == 'dom'){
			return 'Dom';
		}
		else {
			return 'Erro';
		}
	}
	
	// Definir função para mostrar nome do dia completo
	function dia_completo ($a) {
		if ($a == 'Seg' OR $a == '1'){
			return 'Segunda';
		}
		elseif ($a == 'Ter' OR $a == '2'){
			return 'Terça';
		}
		elseif ($a == 'Qua' OR $a == '3'){
			return 'Quarta';
		}
		elseif ($a == 'Qui' OR $a == '4'){
			return 'Quinta';
		}
		elseif ($a == 'Sex' OR $a == '5'){
			return 'Sexta';
		}
		elseif ($a == 'Sab' OR $a == '6'){
			return 'Sábado';
		}
		elseif ($a == 'Dom' OR $a == '7'){
			return 'Domingo';
		}
		else {
			return '';
		}
	}
	
	// Definir função para checar se o MW ou o aprender estão ativos
	function curl_info($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		$content = curl_exec($ch);
		$info = curl_getinfo($ch);

		return $info;
	}
	
	// Definir função para ler as mensagens recebidas
	function processMessage($message, $data) {
		
		$dias = array('Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom');
		$valor_ru = floatval(5.20);
		$valor_ru_cafe = floatval(2.35);
		
		$message_id = $message['message_id'];
		$chat_id = $message['chat']['id'];
		
		// Cria variáveis para o dia atual
		$hoje_numeral = date('N');
		if ($hoje_numeral == 1){
			$hoje = 'Seg';
			$hoje_completo = 'Segunda';
		}
		elseif ($hoje_numeral == 2){
			$hoje = 'Ter';
			$hoje_completo = 'Terça';
		}
		elseif ($hoje_numeral == 3){
			$hoje = 'Qua';
			$hoje_completo = 'Quarta';
		}
		elseif ($hoje_numeral == 4){
			$hoje = 'Qui';
			$hoje_completo = 'Quinta';
		}
		elseif ($hoje_numeral == 5){
			$hoje = 'Sex';
			$hoje_completo = 'Sexta';
		}
		elseif ($hoje_numeral == 6){
			$hoje = 'Sab';
			$hoje_completo = 'Sábado';
		}
		elseif ($hoje_numeral == 7){
			$hoje = 'Dom';
			$hoje_completo = 'Domingo';
		}
		else {
			$hoje = 'bug';
			$hoje_completo = 'crash';
		}
		
		// Verifica se existe conexão com o Banco de Dados; Caso não exista, cria uma nova
		$conexao = mysqli_connect('localhost','{DATABASE}','{PASS}') // Porta, usuário, senha
		or die("Erro na conexão com banco de dados"); // Caso não consiga conectar, mostra a mensagem de erro

		$select_db = mysqli_select_db($conexao, '{DATABASE}'); // Seleciona o banco de dados
			
		if (isset($message['text'])) {
			$text = $message['text']; // Texto recebido na mensagem
			$user = $message['from']['id']; // ID de usuário do Telegram
			$first_name = $message['from']['first_name']; // Primeiro nome do usuário do Telegram
			
			// Cria um "callback" para a variável data -> inline_keyboard
			if ($data != null){
				$text = strval($data);
			}
			
			// Primeiros comandos mostrados
			if ((strpos($text, "/start") === 0)) {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" =>
'Olá, eu sou o seu Gerenciador da UnB!

Depois de configurado, eu poderei te dizer informações sobre sua grade - onde é sua próxima aula, que aulas você terá na sexta, quantas faltas você tem em cada matéria, etc - e, além disso, saberei quantos reais você tem na sua conta do RU e te darei o cardápio atualizado.

Para criar uma conta e começar a usar, basta digitar "/cria";
'));
			}
			
			// Cria nova conta de usuário
			if (strpos($text, "/cria") === 0) {

				date_default_timezone_set('America/Sao_Paulo');
				$created_at = date('d-m-y');
				$saldo = floatval(0);

				$criagrade = "CREATE TABLE IF NOT EXISTS `$user` (Hora VARCHAR(50) NOT NULL, Dom VARCHAR(50) NOT NULL, Seg VARCHAR(50) NOT NULL, Ter VARCHAR(50) NOT NULL, Qua VARCHAR(50) NOT NULL, Qui VARCHAR(50) NOT NULL, Sex VARCHAR(50) NOT NULL, Sab VARCHAR(50) NOT NULL)ENGINE=CSV;";
				mysqli_query($conexao, $criagrade);
				
				$checkn = "SELECT Hora FROM `$user` WHERE hora = '6'";
				$sqlcheckn = mysqli_query($conexao, $checkn);
				$rowsn = mysqli_num_rows($sqlcheckn);
				if ($rowsn == 0) {
					$horarios = array(6, 8, 10, 12, 14, 16, 18, 19, 21);
					foreach ($horarios as $i) {
						$string_sql = "INSERT INTO `$user` (Hora) VALUES ('$i')"; //String com consulta SQL da inserção
						mysqli_query($conexao, $string_sql); // Realiza a consulta
					}
				}
				
				$checkn = "SELECT id FROM user WHERE id = '$user'";
				$sqlcheckn = mysqli_query($conexao, $checkn);
				$rowsn = mysqli_num_rows($sqlcheckn);
				
				if ($rowsn == 0) {
					$string_sql = "INSERT INTO user (id, first_name, created_at, saldo) VALUES ('$user', '$first_name', '$created_at', '$saldo')"; //String com consulta SQL da inserção
					mysqli_query($conexao, $string_sql); // Realiza a consulta
					
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 
'Obrigado, '.$message['from']['first_name'].'! Sua conta foi criada com sucesso!

Agora digite "/tutorial" para aprender a usar.'));
				}

				else {
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Você já possui uma conta!'));
				}
				
				mysqli_close($conexao); // Fecha conexão com banco de dados
			}
			
			// Mostra menu de nova
			if ((strpos($text, "/nova") === 0) AND $text === "/nova") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Escolha o que quer adicionar:', 'reply_markup' => array('inline_keyboard' => 
					array(
						array(
							array('text'=>'Matéria',"callback_data"=>'/nova materia')
						),
						array(
							array('text'=>'Matrícula',"callback_data"=>'/nova matricula')
						),
						array(
							array('text'=>'Prova',"callback_data"=>'/nova prova')
						)
					))));
			}
			
			// Mostra o calendário de matrícula do semestre
			if ((strpos($text, "/calendario") === 0) AND $text === "/calendario") {
				sendMessage("sendPhoto", array('chat_id' => $chat_id, "photo" => 'https://rubensbraz.com/rbsite/telegram/AutomatizaUnB/imagens/cal_semestre/01_2019.jpeg'));
			}
			
			// Sugestões
			if ((strpos($text, "/sugestoes") === 0) AND $text === "/sugestoes") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Olá! Obrigado por usar esse BOT <3.
				
Para mandar alguma sugestão, relatar bugs ou qualquer outra coisa, mande uma mensagem pra quem fez esse negócio: @rubensbraz'));
			}
			
			// Sobre
			if ((strpos($text, "/sobre") === 0) AND $text === "/sobre") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Automatiza UnB - Versão 1.5.1
				
Atualizado dia 29/03/2019.

Uma ratazana de esgoto sempre ajuda outra ratazana de esgoto.


Sanoli, arruma a merda do aplicativo Rumor!!!'));
			}
			
			// change_log
			if ((strpos($text, "/change") === 0) AND $text === "/change") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" =>
'Versão 1.5.1 - 29/03/2019
- Corrigido bug: Não era possível ver o tutorial porque a mensagem ultrapassou o limite de caracteres - tá gigante, né?
* Obrigado, Philipe Serafim!

Versão 1.5.0 - 28/03/2019
- Funções adicionadas: Conjunto de comandos para adicionar e remover matrículas que o usuário queira salvar no seu banco de dados - "/matriculas".

Versão 1.4.0 - 28/03/2019
- Funções adicionadas: Conjunto de comandos para adicionar, remover e atualizar notas das provas - "/provas".
* Obrigado pela ideia, Amanda Shinkawa!

Versão 1.3.0 - 28/03/2019
- Função adicionada: Exibe a última foto publicada no Flickr da UnB usando "/foto".

Versão 1.2.0 - 27/03/2019
- Função adicionada: Links úteis para a UnB (MW, Moodle, Documentos Digitais...) com o comando "/links".
- Função adicionada: Checa se os sites do MW e do Moodle estão ativos com o comando "/funfa".

Versão 1.1.0 - 27/03/2019
- Função adicionada: Agora é possível receber a imagem do calendário de matrícula do semestre com a função "/calendario".

Versão 1.0.2 - 27/03/2019
- Corrigido bug: Não era possível mudar a quantidade de faltas em uma matéria que possui nome composto.
* Obrigado, Laura Leal!

Versão 1.0.1 - 27/03/2019
- Corrigido bug: Matérias adicionadas eram apresentadas de forma duplicada.
* Obrigado, Amanda Shinkawa!

Versão 1.0.0 - 27/03/2019
- Aplicativo lançado.'
));
			}
			
			// Links Úteis
			if ((strpos($text, "/links") === 0) AND $text === "/links") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Links de dor e sofrimento:', 'reply_markup' => array('inline_keyboard' => 
					array(
						array(
							array('text'=>'Matrícula Web','url' => 'https://matriculaweb.unb.br'),
							array('text'=>'Aprender','url' => 'https://aprender.ead.unb.br')
						),
						array(
							array('text'=>'Pergamum (BCE)','url' => 'https://consulta.bce.unb.br/pergamum/biblioteca_s/php/login_usu.php?flag=index.php'),
							array('text'=>'Documentos Digitais','url' => 'https://servicos.unb.br/documentodigital/index.html?#/emitircertidao')
						),
						array(
							array('text'=>'UnB Notícias','url' => 'http://www.noticias.unb.br/')
						)
						))));
			}
			
			// Checa se Funfa: vê se sites estão ativos
			if ((strpos($text, "/funfa") === 0) AND $text === "/funfa") {
				$mw = 'https://matriculaweb.unb.br';
				$info_mw = curl_info($mw);
				if( $info_mw['http_code']==200 ) {
					$ar_mw = "MatrículaWEB ativo!";
				}
				else {
					$ar_mw = "MatrículaWEB fora do ar!";
				}

				$md = 'https://aprender.ead.unb.br';
				$info_md = curl_info($md);
				if( $info_md['http_code']==200 ) {
					$ar_md = "Moodle ativo!";
				}
				else {
					$ar_md = "Moodle fora do ar!";
				}
				
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" =>
$ar_mw."
".$ar_md));
			}
			
			// Mostra a última foto do Flickr
			if ((strpos($text, "/foto") === 0) AND $text === "/foto") {
				$links = ftflickr();
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => "A última foto postada no Flickr da UnB é essa: ".$links));
			}
			
			// Comandos Ajuda ------------------------------------------------------------------------------------
			// Mostra tutorial inicial - parte 1
			if ((strpos($text, "/tutorial") === 0) AND $text === "/tutorial") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" =>
'Olá, '.$message['from']['first_name'].'! Espero que eu consiga te ajudar.

----- GRADE -----
Antes de tudo você deve preencher a sua grade. Ela segue a divisão de horários da UnB e vale para todos os dias da semana.

Use o comando "/nova materia" para obter o modelo abaixo, preencha-o e mande a mensagem para adicionar uma matéria!

"Nova Matéria
Dia:
Horário:
Matéria:
Local:
Créditos:"

Os dias podem assumir os seguintes valores: Dom, Seg, Ter, Qua, Qui, Sex, Sab; - não é case sensitive
As horas assumem esses valores inteiros: 6, 8, 10, 12, 14, 16, 18, 19, 21;
Local assume qualquer texto que você inserir;
Créditos é a quantidade de créditos da sua matéria (2, 4, 6...);

* Se a matéria possuir várias aulas, preencha todas com exatamente o mesmo nome nos vários dias.

Exemplo 1:
"Nova Matéria
Dia: Qui
Horário: 10
Matéria: ISOL
Local: ICC, Anf 2
Créditos: 4" -> Adiciona uma aula de ISOL quinta-feira, às 10h, no ICC, Anf 2 e a matéria possui 4 créditos.

Para remover uma matéria da sua grade, use esse modelo de comando: "/del - Dia - Aula";

Exemplo 2: "/del - Qui - ISOL" -> Remove a matéria que foi inserida no Exemplo 1;

Para deletar todas as matérias da sua grade, use o comando "/del todas";
* ATENÇÃO: esse comando não pode ser desfeito!

Para ver sua grade completa, o comando é "/grade"; - comando não muito recomendável por floodar a conversa - exibe a grade de todos os dias da semana;
Para ver a grade para um dia específico, use "/grade *", onde * é o dia (seg, ter, qua...);
* DICA LEGAL: use "/grade hoje" para ver a grade do dia atual.

Exemplo 3: "/grade quinta" -> mostra a grade de quinta.

Exemplo 4: "/grade hoje" -> mostra a grade do dia atual.

Para ver os locais das suas aulas, o comando é "/locais".

* DICA LEGAL: Para ver os links para o site do MW e do Moodle, use "/links". Caso você esteja em dúvidas se os sites não estão funcionando só para você, use o comando "/funfa" para saber se o site está no ar!

Preencha todas as suas aulas e depois siga o próximo passo!


----- PROVAS -----
Uma parte das funcionalidades desse BOT que salvam vidas é essa!

Para adicionar uma nova prova, use o comando "/nova prova" para obter o modelo abaixo:

Nova Prova
Data: 
Nome: 
Matéria: 
Peso: 
Nota: 

Exemplo:
"Nova Prova
Data: 20-04-2020
Nome: P1
Matéria: ISOL 
Peso: 3
Nota: "

Faça uma cópia da mensagem que você recebeu, preencha os dados e envie, como no exemplo acima.
Você precisa adicionar a matéria antes de adicionar a prova!

Inicialmente você pode deixar a nota vazia, para preenchê-la, use: "/nota - Nome - Matéria - Nota";
Exemplo: "/nota - P1 - ISOL - 10".

Para remover uma prova, use: "/delprova - Nome - Matéria";

E finalmente, para ver as provas salvas, use: "/provas".


-> Para continuar vendo o tutorial, use "/tutorial2"'));
			}
			
			// Mostra tutorial inicial - parte 2
			if ((strpos($text, "/tutorial") === 0) AND $text === "/tutorial2") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" =>
'----- MATRÍCULAS -----
Esse conjunto de comandos servem para você adicionar matrículas de outras pessoas ao seu banco de dados.
Sabe quando você vai fazer alguma matéria que tem um projeto em grupo e você não sabe a matrícula do pessoal?
Pois é, agora vai!

Para adicionar uma nova prova, use o comando "/nova matricula" para obter o modelo abaixo:

Nova Matricula
Nome: 
Matrícula:  

Exemplo:
"Nova Matrícula
Nome: Rubens Braz
Matrícula: 19/0133456"

Faça uma cópia da mensagem que você recebeu, preencha os dados e envie, como no exemplo acima.

Para remover uma matricula, use: "/delmatricula - Nome - Matrícula";
Exemplo: "/delmatricula - Rubens Braz - 19/0133456".

E finalmente, para ver as matrículas salvas, use: "/matriculas".


----- FALTAS -----
Quando você adiciona matérias na sua grade, automaticamente são criados contadores para o número de faltas em cada uma.

Para ver o número de faltas em todas as matérias, use o comando: "/faltas";
Para ver o número de faltas em cada matéria, use o comando: "/faltas %", % é o nome da matéria que você adicionou na sua grade;
Para alterar a quantidade de faltas, o comando é bem parecido, use "/faltas # %", onde # pode ser "+" ou "-" e significa a adição ou remoção de faltas e %  é o nome da matéria que você adicionou na sua grade.

Exemplo 5: "/faltas ISOL" -> mostra a quantidade de faltas em ISOL.

Exemplo 6: "/faltas" -> mostra a quantidade de faltas em todas as matérias cadastradas.

Exemplo 7: "/faltas + ISOL" -> adiciona uma falta em ISOL.

* DICA LEGAL: Se você não se lembrar como nomeou as matérias para usar os comandos, digite "/materias" para ver todas elas.


----- RU ------
Agora você vai configurar sua quantidade de créditos no RU. Adicione a quantidade atual de créditos em reais com o comando: "/ru # @", onde # pode ser "+" ou "-" e significa a adição ou remoção de dinheiro na sua conta e, @ é o valor em reais da transação.

Exemplo 8: "/ru + 20.5" -> adição de R$ 20,50 na conta.

Para ver o cardápio do RU, use o comando: "/cardapio";

Para ver quantos reais você tem no RU, digite "/saldo";

Para remover um crédito (R$ 5,20) use o comando "/ru";
Caso você deseje remover o valor de um café da manhã (R$ 2,35), o comando é "/ru cafe".

Exemplo 9: "/ru" -> remove o valor de uma refeição (almoço / jantar) do seu saldo.


*******************
Sempre que você esquecer o que está aqui nesse tutorial (já esqueceu), use o comando "/ajuda" para obter ajuda específica para cada item. Obrigado por ler!

Esse bot será atualizado aos poucos, para checar as novidades, use o comando "/change" para ver quando e quais foram as novidades implementadas!
*******************'));
			}
			
			// Mostra menu de ajuda
			if ((strpos($text, "/ajuda") === 0) AND $text === "/ajuda") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Olá, '. $message['from']['first_name'].'! Escolha uma das opções abaixo para ver a ajuda detalhada.', 'reply_markup' => array('inline_keyboard' => 
					array(
						array(
							array('text'=>'Grade',"callback_data"=>'/ajuda grade'),
							array('text'=>'RU',"callback_data"=>'/ajuda ru')
						),
						array(
							array('text'=>'Matrículas',"callback_data"=>'/ajuda matricula'),
							array('text'=>'Provas',"callback_data"=>'/ajuda provas')
						),
						array(
							array('text'=>'Faltas',"callback_data"=>'/ajuda faltas'),
							array('text'=>'Tutorial Completo',"callback_data"=>'/tutorial')
						),
						array(
							array('text'=>'Sobre',"callback_data"=>'/sobre'),
							array('text'=>'Sugestões',"callback_data"=>'/sugestoes')
						),
						array(
							array('text'=>'Novidades!',"callback_data"=>'/change')
						)
						))));
			}
			
			// Mostra ajuda da grade
			if ((strpos($text, "/ajuda") === 0) AND $text === "/ajuda grade") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 
'----- GRADE -----

 - ADICIONAR AULA NA GRADE: 
Use o comando "/nova materia" para obter o modelo abaixo:

Nova Matéria
Dia: 
Horário: 
Matéria: 
Local: 
Créditos: 

Exemplo:
"Nova Matéria
Dia: Qui
Horário: 10
Matéria: ISOL
Local: ICC, Anf 2
Créditos: 4"
 
Dia = (Dom, Seg, Ter, Qua, Qui, Sex, Sab); - não é case sensitive
Hora = (6, 8, 10, 12, 14, 16, 18, 19, 21);
Aula = qualquer texto;
Local = qualquer texto;
Créditos = (2, 4, 6, 8);

* Recomendo nomear as matérias com siglas, você vai precisar usar os nomes para se referir a elas.
* Se a matéria possuir várias aulas, preencha todas com exatamente o mesmo nome nos vários dias.


- REMOVER AULA NA GRADE:
"/del - Dia - Aula";
Exemplo: "/del - qui - ISOL".


- REMOVER TODAS AS AULAS DA GRADE:
"/del todas";

* ATENÇÃO: esse comando não pode ser desfeito!


- VER SUA GRADE COMPLETA:
"/grade";


- VER GRADE EM UM DIA:
"/grade Dia";
Exemplo: "/grade quinta".

Dia = (Dom, Seg, Ter, Qua, Qui, Sex, Sab);

DICA LEGAL: use "/grade hoje" para ver a grade do dia atual.


- VER TODOS OS LOCAIS DE AULAS:
"/locais";



* DICA LEGAL: Para ver os links para o site do MW e do Moodle, use "/links". Caso você esteja em dúvidas se os sites não estão funcionando só para você, use o comando "/funfa" para saber se o site está no ar!

PS: Esse bot será atualizado aos poucos, para checar as novidades, use o comando "/change" para ver quando e quais foram as novidades implementadas!'));
			}
			
			// Mostra ajuda do RU
			if ((strpos($text, "/ajuda ru") === 0) AND $text === "/ajuda ru") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 
'----- RU ------
- ADICIONAR OU REMOVER SALDO:
"/ru # valor";
Exemplo: "/ru + 20.5".

# = (+, -);
Valor = valor da transação em reais.


- REMOVER O VALOR DE UMA REFEIÇÃO:
"/ru" ou "/ru cafe"

"/ru" remove o valor de um almoço ou jantar;
"/ru cafe" remove o valor de um café da manhã.


- VER CARDÁPIO:
"/cardapio";


- VER SEU SALDO:
"/saldo";


PS: Esse bot será atualizado aos poucos, para checar as novidades, use o comando "/change" para ver quando e quais foram as novidades implementadas!'));
			}
			
			// Mostra ajuda de matrícula
			if ((strpos($text, "/ajuda") === 0) AND $text === "/ajuda matricula") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 
'----- MATRÍCULAS ------
- ADICIONAR MATRÍCULA:
Use o comando "/nova matricula" para obter o modelo abaixo:

Nova Matricula
Nome: 
Matrícula:  

Exemplo:
"Nova Matrícula
Nome: Rubens Braz
Matrícula: 19/0133456"
 
 
- REMOVER MATRÍCULA:
"/delmatricula - Nome - Matrícula";
Exemplo: "/delmatricula - Rubens Braz - 19/0133456"


- VER TODAS AS MATRÍCULAS:
"/matriculas";


PS: Esse bot será atualizado aos poucos, para checar as novidades, use o comando "/change" para ver quando e quais foram as novidades implementadas!'));
			}
			
			// Mostra ajuda de provas
			if ((strpos($text, "/ajuda provas") === 0) AND $text === "/ajuda provas") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 
'----- PROVAS ------
- ADICIONAR PROVA:
Use o comando "/nova prova" para obter o modelo abaixo:

Nova Prova
Data: 
Nome: 
Matéria: 
Peso: 
Nota: 

Exemplo:
"Nova Prova
Data: 20-04-2020
Nome: P1
Matéria: ISOL 
Peso: 3
Nota: "


- INSERIR NOTA DE UMA PROVA:
"/nota - Nome - Matéria - Nota";
Exemplo: "/nota - P1 - ISOL - 5.5"


- REMOVER UMA PROVA:
"/delprova - Nome - Matéria";


- VER PROVAS:
"/provas";


PS: Esse bot será atualizado aos poucos, para checar as novidades, use o comando "/change" para ver quando e quais foram as novidades implementadas!'));
			}
			
			// Mostra ajuda de faltas
			if ((strpos($text, "/ajuda") === 0) AND $text === "/ajuda faltas") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 
'----- Faltas -----
- VER QUANTIDADE DE FALTAS:
"/faltas";


- VER QUANTIDADE DE FALTAS EM UMA MATÉRIA:
"/faltas Matéria";
Exemplo: "/faltas ISOL".


- ADICIONAR OU REMOVER FALTAS EM UMA MATÉRIA:
"/faltas # Matéria";
Exemplo: "/faltas + ISOL";

# = (+, -);
Matéria = como você nomeou a matéria.

DICA LEGAL: Se você não se lembrar como nomeou as matérias para usar os comandos, digite "/materias" para ver todas elas.


PS: Esse bot será atualizado aos poucos, para checar as novidades, use o comando "/change" para ver quando e quais foram as novidades implementadas!'));
			}
			
			// Comandos RU ------------------------------------------------------------------------------------
			// Adiciona ou remove R$
			if ((strpos($text, "/ru") === 0) AND $text != "/ru") {
				$copiatexto = $text;
				$copiatexto = str_replace(" ", "", $copiatexto);
				$pedacos = explode("/ru", $copiatexto);
				array_shift($pedacos);
				$tipo = $pedacos[0][0];
				$valor = $pedacos[0][1].$pedacos[0][2].$pedacos[0][3].$pedacos[0][4].$pedacos[0][5].$pedacos[0][6];
				$valor = floatval(str_replace(",", ".", $valor));
				
				$saldo = mysqli_query($conexao, "SELECT saldo FROM user WHERE id='$user'");
				
				// Tira saldo
				if ($tipo == '-'){
					$string_sql = "UPDATE user SET saldo = saldo - $valor WHERE id='$user'"; // String SQL
					mysqli_query($conexao, $string_sql);
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Você removeu R$ '. formata_rs($valor)));
					$sql = mysqli_query($conexao, "SELECT saldo FROM user WHERE id='$user'");
					while($exibe = mysqli_fetch_assoc($sql)){
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Seu saldo atual é: R$ '.formata_rs($exibe["saldo"])));
					}
				}
				
				// Acrescenta saldo
				if ($tipo == '+'){
					$string_sql = "UPDATE user SET saldo = saldo + $valor WHERE id='$user'"; // String SQL
					mysqli_query($conexao, $string_sql);
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Você adicionou R$ '. formata_rs($valor)));
					$sql = mysqli_query($conexao, "SELECT saldo FROM user WHERE id='$user'");
					while($exibe = mysqli_fetch_assoc($sql)){
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Seu saldo atual é: R$ '.formata_rs($exibe["saldo"])));
					}
				}
			}
			
			// Remove um crédito de R$ 5,20
			if ((strpos($text, "/ru") === 0) AND $text === "/ru") {
				$saldo = mysqli_query($conexao, "SELECT saldo FROM user WHERE id='$user'");
				$string_sql = "UPDATE user SET saldo = saldo - $valor_ru WHERE id='$user'"; // String SQL
				mysqli_query($conexao, $string_sql);
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => '1 crédito removido (R$ 5,20)'));
				$sql = mysqli_query($conexao, "SELECT saldo FROM user WHERE id='$user'");
				while($exibe = mysqli_fetch_assoc($sql)){
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Seu saldo atual é: R$ '.formata_rs($exibe["saldo"])));
				}
			}
			
			// Remove um crédito de R$ 2,35
			if ((strpos($text, "/ru cafe") === 0) AND (($text === "/ru cafe") OR ($text === "/ru café"))) {
				$saldo = mysqli_query($conexao, "SELECT saldo FROM user WHERE id='$user'");
				$string_sql = "UPDATE user SET saldo = saldo - $valor_ru_cafe WHERE id='$user'"; // String SQL
				mysqli_query($conexao, $string_sql);
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => '1 crédito removido (R$ 2,35)'));
				$sql = mysqli_query($conexao, "SELECT saldo FROM user WHERE id='$user'");
				while($exibe = mysqli_fetch_assoc($sql)){
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Seu saldo atual é: R$ '.formata_rs($exibe["saldo"])));
				}
			}
			
			// Mostra saldo
			if (strpos($text, "/saldo") === 0) {
				$sql = mysqli_query($conexao, "SELECT saldo FROM user WHERE id='$user'");
				while($exibe = mysqli_fetch_assoc($sql)){
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Seu saldo atual é: R$ '.formata_rs($exibe["saldo"])));
				}
			}
			
			// Mostra o cardápio
			if ((strpos($text, "/cardapio") === 0) AND $text === "/cardapio") {
				$links = cardapioRU();
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Olá, '. $message['from']['first_name'].'! Escolha uma das opções abaixo para ver o cardápio no pdf feito pela Sanoli.', 'reply_markup' => array('inline_keyboard' => 
					array(
						array(
							array('text'=>'Darcy 1','url'=>$links[0]),//botão 1
							array('text'=>'Darcy 2','url'=>$links[1])//botão 2
						),
						array(
							array('text'=>'Outros Campi 1','url'=>$links[2]),//botão 3
							array('text'=>'Outros Campi 2','url'=>$links[3])//botão 4
						)
					))));
			}
			
			// Comandos Grade ----------------------------------------------------------------------------------
			// Adiciona matéria na grade inline
			if ((strpos($text, "/nova") === 0) AND $text != "/nova" AND $text != "/nova materia" AND $text != "/nova prova" AND $text != "/nova matricula") {
				$copiatexto = $text;
				$pedacos = explode("/nova", $copiatexto);
				array_shift($pedacos);
				$pedacos = explode(" - ", $pedacos[0]);
				array_shift($pedacos);
				$dia = trata_dia($pedacos[0]);
				$string_sql = "UPDATE `$user` SET `$dia` = '$pedacos[2]' WHERE Hora = '$pedacos[1]'"; // String SQL
				mysqli_query($conexao, $string_sql);

				$criagrade = "CREATE TABLE IF NOT EXISTS `materias_$user` (local VARCHAR(50) NOT NULL, materias VARCHAR(50) NOT NULL, faltas int NOT NULL, creditos int NOT NULL)ENGINE=CSV;";
				mysqli_query($conexao, $criagrade);
				
				$checkn = "SELECT materias FROM `materias_$user` WHERE materias = '$pedacos[2]'";
				
				$sqlcheckn= mysqli_query($conexao, $checkn);
				$rowsn = mysqli_num_rows($sqlcheckn);

				if (count($pedacos) >= 5){
					if ($rowsn == 0) {
						$string_sql = "INSERT INTO `materias_$user` (materias, local, creditos) VALUES ('$pedacos[2]','$pedacos[3]','$pedacos[4]')"; //String com consulta SQL da inserção
						mysqli_query($conexao, $string_sql); // Realiza a consulta
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Matéria adicionada!'));
					}
					else {
						$string_sql = "UPDATE `materias_$user` SET local = '$pedacos[3]', creditos = '$pedacos[4]' WHERE materias = '$pedacos[2]'"; // String SQL
						mysqli_query($conexao, $string_sql);
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Matéria adicionada!'));
					}
				}
				else {
					sendMessage("sendMessage", array('chat_id' => $chat_id,
"text" =>'Corrija seu comando, tem alguma coisa errada!
Para obter ajuda use "/ajuda".'));
				}
			}
			
			// Adiciona matéria na grade bloco
			if (strpos($text, "Nova Matéria") === 0) {
				$copiatexto = $text;
				$pedacos = explode("\n", $copiatexto);
				array_shift($pedacos);
				foreach ($pedacos as $fatias) {
					$final = explode(":", $fatias);
					$final[1] = trim($final[1]);
					$dadosfinais[] = $final[1];
				}
				
				$pedacos = $dadosfinais;
				
				$dia = trata_dia($pedacos[0]);
				$string_sql = "UPDATE `$user` SET `$dia` = '$pedacos[2]' WHERE Hora = '$pedacos[1]'"; // String SQL
				mysqli_query($conexao, $string_sql);

				$criagrade = "CREATE TABLE IF NOT EXISTS `materias_$user` (local VARCHAR(50) NOT NULL, materias VARCHAR(50) NOT NULL, faltas int NOT NULL, creditos int NOT NULL)ENGINE=CSV;";
				mysqli_query($conexao, $criagrade);
				
				$checkn = "SELECT materias FROM `materias_$user` WHERE materias = '$pedacos[2]'";
				
				$sqlcheckn= mysqli_query($conexao, $checkn);
				$rowsn = mysqli_num_rows($sqlcheckn);

				if (count($pedacos) >= 5){
					if ($rowsn == 0) {
						$string_sql = "INSERT INTO `materias_$user` (materias, local, creditos) VALUES ('$pedacos[2]','$pedacos[3]','$pedacos[4]')"; //String com consulta SQL da inserção
						mysqli_query($conexao, $string_sql); // Realiza a consulta
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Matéria adicionada!'));
					}
					else {
						$string_sql = "UPDATE `materias_$user` SET local = '$pedacos[3]', creditos = '$pedacos[4]' WHERE materias = '$pedacos[2]'"; // String SQL
						mysqli_query($conexao, $string_sql);
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Matéria adicionada!'));
					}
				}
				else {
					sendMessage("sendMessage", array('chat_id' => $chat_id,
"text" =>'Corrija seu comando, tem alguma coisa errada!
Para obter ajuda use "/ajuda".'));
				}
			}
			
			// Mostra modelo de adição de matéria
			if ((strpos($text, "/nova materia") === 0) AND $text == "/nova materia") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" =>
'Nova Matéria
Dia: 
Horário: 
Matéria: 
Local: 
Créditos: '));
			}
			
			// Remove matéria na grade
			if ((strpos($text, "/del") === 0) AND $text != "/del todas" AND (strpos($text, "/delprova") != 0)) {
				$copiatexto = $text;
				$pedacos = explode("/del", $copiatexto);
				array_shift($pedacos);
				$pedacos = explode(" - ", $pedacos[0]);
				array_shift($pedacos);
				$dia = trata_dia($pedacos[0]);

				$string_sql2 = "UPDATE `$user` SET `$dia` = '' WHERE $dia = '$pedacos[1]'"; // String SQL
				$sqlcheckn = mysqli_query($conexao, $string_sql2);
				$rowsn2 = mysqli_affected_rows($conexao);
				
				if ($rowsn2 > 0){
					$string_sql = "DELETE FROM `materias_$user` WHERE materias = '$pedacos[1]'"; // String SQL
					$sqlcheckn= mysqli_query($conexao, $string_sql);
					$rowsn = mysqli_affected_rows($conexao);
				}
				if ($rowsn > 0) {
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Matéria removida!'));
				}
				else {
					sendMessage("sendMessage", array('chat_id' => $chat_id,
"text" =>'Corrija seu comando, tem alguma coisa errada!
Para obter ajuda use "/ajuda".'));
				}
			}
			
			// Remove todas as matérias da grade
			if ((strpos($text, "/del todas") === 0) AND $text == "/del todas" AND $text != "/delprova") {
				$string_sql = "DELETE FROM `materias_$user`"; // String SQL
				mysqli_query($conexao, $string_sql);
				
				$string_sql2 = "DROP TABLE `$user`"; // String SQL
				mysqli_query($conexao, $string_sql2);
				
				$criagrade = "CREATE TABLE IF NOT EXISTS `$user` (Hora VARCHAR(50) NOT NULL, Dom VARCHAR(50) NOT NULL, Seg VARCHAR(50) NOT NULL, Ter VARCHAR(50) NOT NULL, Qua VARCHAR(50) NOT NULL, Qui VARCHAR(50) NOT NULL, Sex VARCHAR(50) NOT NULL, Sab VARCHAR(50) NOT NULL)ENGINE=CSV;";
				mysqli_query($conexao, $criagrade);
				
				$checkn = "SELECT Hora FROM `$user` WHERE hora = '6'";
				$sqlcheckn = mysqli_query($conexao, $checkn);
				$rowsn = mysqli_num_rows($sqlcheckn);
				if ($rowsn == 0) {
					$horarios = array(6, 8, 10, 12, 14, 16, 18, 19, 21);
					foreach ($horarios as $i) {
						$string_sql = "INSERT INTO `$user` (Hora) VALUES ('$i')"; //String com consulta SQL da inserção
						mysqli_query($conexao, $string_sql); // Realiza a consulta
					}
				}
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Todas as matérias foram removidas!'));
			}
			
			// Mostra todas as matérias
			if ((strpos($text, "/materias") === 0) AND $text == "/materias") {
				$sql = mysqli_query($conexao, "SELECT materias FROM `materias_$user`");
				$mensagem = 'Suas matérias são as seguintes:
';
				while($exibe = mysqli_fetch_assoc($sql)){
					$mensagem = $mensagem.$exibe["materias"]."
";
				}
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem));
			}
			
			// Opções de grade
			if (strpos($text, "/grade") === 0){
				
				// Mostra a grade completa
				if ($text === "/grade") {
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Sua grade completa é:'));
					foreach ($dias as $dia){
						$mensagem = trata_dia($dia).":

";
						$horarios = array(6, 8, 10, 12, 14, 16, 18, 19, 21);
						foreach ($horarios as $i){
							$sql = mysqli_query($conexao, "SELECT * FROM `$user` WHERE Hora = $i");
							while($exibe = mysqli_fetch_assoc($sql)){
								$mensagem = $mensagem.$exibe['Hora'].": ".$exibe[$dia]." - ";
								
								$sql2 = mysqli_query($conexao, "SELECT local FROM `materias_$user` WHERE `materias` = '$exibe[$dia]'");
								
								$exibe2 = mysqli_fetch_assoc($sql2);
								$mensagem = $mensagem.$exibe2['local'];

								$mensagem = $mensagem."
								
";
							}
						}
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem));
					}
				}
				
				// Mostra a grade do dia atual
				if ($text === "/grade hoje" OR $text === "/grade hj") {
					$mensagem = "Sua grade para hoje, ".$hoje_completo.", é:

";
					$horarios = array(6, 8, 10, 12, 14, 16, 18, 19, 21);
					foreach ($horarios as $i){
						$sql = mysqli_query($conexao, "SELECT * FROM `$user` WHERE Hora = $i");
						while($exibe = mysqli_fetch_assoc($sql)){
							$mensagem = $mensagem.$exibe['Hora'].": ".$exibe[$hoje]." - ";
							
							$sql2 = mysqli_query($conexao, "SELECT local FROM `materias_$user` WHERE `materias` = '$exibe[$hoje]'");
							
							$exibe2 = mysqli_fetch_assoc($sql2);
							$mensagem = $mensagem.$exibe2['local'];

							$mensagem = $mensagem."
							
";
						}
					}
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem));
				}
				
				// Mostra grade para um dia da semana
				if ((strpos($text, "/grade ") === 0) AND ($text != "/grade hoje") AND ($text != "/grade hj")){
					$text = explode("/grade ", $text);
					array_shift($text);
					$dia = $text[0];
					$dia = trata_dia($dia);
					$mensagem = "Sua grade para ".dia_completo($dia)." é:

";
					$horarios = array(6, 8, 10, 12, 14, 16, 18, 19, 21);
					foreach ($horarios as $i){
						$sql = mysqli_query($conexao, "SELECT * FROM `$user` WHERE Hora = $i");
						while($exibe = mysqli_fetch_assoc($sql)){
							$mensagem = $mensagem.$exibe['Hora'].": ".$exibe[$dia]." - ";
							
							$sql2 = mysqli_query($conexao, "SELECT local FROM `materias_$user` WHERE `materias` = '$exibe[$dia]'");
							
							$exibe2 = mysqli_fetch_assoc($sql2);
							$mensagem = $mensagem.$exibe2['local'];
							
							$mensagem = $mensagem."
							
";
						}
					}
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem));
				}
			}
			
			// Mostra todos os locais das matérias
			if ((strpos($text, "/locais") === 0) AND $text === "/locais") {
				$faltas = mysqli_query($conexao, "SELECT * FROM `materias_$user`");
				$mensagem = 'Suas aulas são nesses locais:
';
				while($exibe = mysqli_fetch_assoc($faltas)){
					$linha = $exibe['materias'].': '.$exibe['local'].'
';
					$mensagem = $mensagem.$linha;
				}
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem));
			}
			
			// Comandos Faltas ------------------------------------------------------------------------------------
			// Adiciona ou remove faltas
			if ((strpos($text, "/faltas") === 0) AND $text != "/faltas") {
				$copiatexto = $text;
				$pedacos = explode("/faltas", $copiatexto);
				array_shift($pedacos);
				$pedacos[0] = trim($pedacos[0]);
				$tipo = $pedacos[0][0];
				$materia = $pedacos[0];
				$materia = str_replace("+", "", $materia);
				$materia = str_replace("-", "", $materia);
				$materia = trim($materia);
				
				$faltas = mysqli_query($conexao, "SELECT faltas FROM `materias_$user` WHERE materias='$materia'");
				
				// Tira faltas
				if ($tipo == '-'){
					$string_sql = "UPDATE `materias_$user` SET faltas = faltas - 1 WHERE materias='$materia'"; // String SQL
					$sqlcheckn = mysqli_query($conexao, $string_sql);
					$rowsn = mysqli_affected_rows($conexao);
					if ($rowsn > 0){
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Menos uma falta em '.$materia));
						$sql = mysqli_query($conexao, "SELECT faltas FROM `materias_$user` WHERE materias='$materia'");
						while($exibe = mysqli_fetch_assoc($sql)){
							sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Seu número de faltas atual em '.$materia.' é: '.$exibe["faltas"]));
						}
					}
					else {
						sendMessage("sendMessage", array('chat_id' => $chat_id,
"text" =>'Corrija seu comando, tem alguma coisa errada!
Para obter ajuda use "/ajuda".'));
					}
				}
				
				// Acrescenta faltas
				if ($tipo == '+'){
					$string_sql = "UPDATE `materias_$user` SET faltas = faltas + 1 WHERE materias='$materia'"; // String SQL
					$sqlcheckn = mysqli_query($conexao, $string_sql);
					$rowsn = mysqli_affected_rows($conexao);
					if ($rowsn > 0){
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Mais uma falta em '.$materia));
						$sql = mysqli_query($conexao, "SELECT faltas FROM `materias_$user` WHERE materias='$materia'");
						while($exibe = mysqli_fetch_assoc($sql)){
							sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Seu número de faltas atual em '.$materia.' é: '.$exibe["faltas"]));
						}
					}
					else {
						sendMessage("sendMessage", array('chat_id' => $chat_id,
"text" =>'Corrija seu comando, tem alguma coisa errada!
Para obter ajuda use "/ajuda".'));
					}
				}
			}
			
			// Mostra a quantidade de faltas em uma matéria
			if ((strpos($text, "/faltas") === 0) AND $text != "/faltas") {
				$copiatexto = $text;
				$pedacos = explode("/faltas ", $copiatexto);
				array_shift($pedacos);
				$materia = $pedacos[0];
				$sql = mysqli_query($conexao, "SELECT * FROM `materias_$user` WHERE materias='$materia'");
				while($exibe = mysqli_fetch_assoc($sql)){
					if ($exibe["creditos"] == 2){
						$maxfaltas = 3;
					}
					if ($exibe["creditos"] == 4){
						$maxfaltas = 7;
					}
					if ($exibe["creditos"] == 6){
						$maxfaltas = 11;
					}
					if ($exibe["creditos"] == 8){
						$maxfaltas = 15;
					}
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'A quantidade atual de faltas em '.$materia.' é: '.$exibe["faltas"].' de '.$maxfaltas));
				}
			}
			
			// Mostra todas as faltas
			if ((strpos($text, "/faltas") === 0) AND $text === "/faltas") {
				$faltas = mysqli_query($conexao, "SELECT * FROM `materias_$user`");
				$mensagem = 'Suas faltas são:
';
				while($exibe = mysqli_fetch_assoc($faltas)){
					if ($exibe["creditos"] == 2){
						$maxfaltas = 3;
					}
					if ($exibe["creditos"] == 4){
						$maxfaltas = 7;
					}
					if ($exibe["creditos"] == 6){
						$maxfaltas = 11;
					}
					if ($exibe["creditos"] == 8){
						$maxfaltas = 15;
					}
					$linha = $exibe['materias'].': '.$exibe['faltas'].' de '.$maxfaltas.'
';
					$mensagem = $mensagem.$linha;
					$maxfaltas = "";
				}
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem));
			}
			
			
			// Provas ----------------------------------------------------------------------------------
			// Mostra modelo de adição de prova
			if ((strpos($text, "/nova prova") === 0) AND $text === "/nova prova") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Nova Prova
Data: 
Nome: 
Matéria: 
Peso: 
Nota: '));
			}

			// Adiciona provas no banco de dados
			if (strpos($text, "Nova Prova") === 0) {
				$copiatexto = $text;
				$pedacos = explode("\n", $copiatexto);
				array_shift($pedacos);
				foreach ($pedacos as $fatias) {
					$final = explode(":", $fatias);
					$final[1] = trim($final[1]);
					$dadosfinais[] = $final[1];
				}
				
				$pedacos = $dadosfinais;
				
				$criaprovas = "CREATE TABLE IF NOT EXISTS `provas_$user` (data VARCHAR(50) NOT NULL, nome VARCHAR(50) NOT NULL, materia VARCHAR(50) NOT NULL, peso FLOAT NOT NULL, nota FLOAT NOT NULL)ENGINE=CSV;";
				mysqli_query($conexao, $criaprovas);
				
				$checkn = "SELECT materias FROM `materias_$user` WHERE materias = '$pedacos[2]'";
				$sqlcheckn = mysqli_query($conexao, $checkn);
				$rowsn = mysqli_num_rows($sqlcheckn);
				
				$checkn2 = "SELECT * FROM `provas_$user` WHERE materia = '$pedacos[2]' AND nome='$pedacos[1]'";
				$sqlcheckn2 = mysqli_query($conexao, $checkn2);
				$rowsn2 = mysqli_num_rows($sqlcheckn2);

				if ($rowsn > 0){
					if ($rowsn2 == 0){
						$string_sql = "INSERT INTO `provas_$user` (data, nome, materia, peso, nota) VALUES (STR_TO_DATE('$pedacos[0]', '%d-%m-%y'),'$pedacos[1]','$pedacos[2]','$pedacos[3]','$pedacos[4]')"; // String SQL
						mysqli_query($conexao, $string_sql);
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Prova adicionada!'));
					}
					else {
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Essa prova já foi adicionada!'));
					}
				}
			}
			
			// Altera nota de uma prova
			if ((strpos($text, "/nota") === 0) AND $text != "/nota") {
				$copiatexto = $text;
				$pedacos = explode("/nota", $copiatexto);
				array_shift($pedacos);
				$pedacos = explode(" - ", $pedacos[0]);
				array_shift($pedacos);

				$string_sql = "UPDATE `provas_$user` SET nota = '$pedacos[2]' WHERE nome = '$pedacos[0]' AND materia = '$pedacos[1]'"; // String SQL
				$sqlcheckn= mysqli_query($conexao, $string_sql);
				$rowsn = mysqli_affected_rows($conexao);
				
				if ($rowsn > 0) {
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Nota atualizada!'));
				}
				else {
					sendMessage("sendMessage", array('chat_id' => $chat_id,
"text" =>'Corrija seu comando, tem alguma coisa errada!
Para obter ajuda use "/ajuda".'));
				}
			}
			
			// Remove uma prova
			if ((strpos($text, "/delprova") === 0) AND $text != "/delprova") {
				$copiatexto = $text;
				$pedacos = explode("/delprova", $copiatexto);
				array_shift($pedacos);
				$pedacos = explode(" - ", $pedacos[0]);
				array_shift($pedacos);

				$string_sql = "DELETE FROM `provas_$user` WHERE nome = '$pedacos[0]' AND materia = '$pedacos[1]'"; // String SQL

				$sqlcheckn= mysqli_query($conexao, $string_sql);
				$rowsn = mysqli_affected_rows($conexao);
			
				if ($rowsn > 0) {
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Prova removida!'));
				}
				else {
					sendMessage("sendMessage", array('chat_id' => $chat_id,
"text" =>'Corrija seu comando, tem alguma coisa errada!
Para obter ajuda use "/ajuda".'));
				}
			}
			
			// Mostra todas as provas
			if ((strpos($text, "/provas") === 0) AND $text === "/provas") {
				$provas = mysqli_query($conexao, "SELECT *, DATE_FORMAT(data,'%d-%m-%y') AS data FROM `provas_$user` ORDER BY data ASC");
				
				$mensagem = 'Suas provas serão nesses dias:
';
				while($exibe = mysqli_fetch_assoc($provas)){
					$linha = '* '.$exibe['data'].', '.$exibe['nome'].' - '.$exibe['materia'].' 
Peso: '.$exibe['peso'].'. Nota: '.$exibe['nota'].'

';
					$mensagem = $mensagem.$linha;
				}
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem));
			}
			
			// Matrícula ----------------------------------------------------------------------------------
			// Adiciona matrícula no banco de dados
			if (strpos($text, "Nova Matrícula") === 0) {
				$copiatexto = $text;
				$pedacos = explode("\n", $copiatexto);
				array_shift($pedacos);
				foreach ($pedacos as $fatias) {
					$final = explode(":", $fatias);
					$final[1] = trim($final[1]);
					$dadosfinais[] = $final[1];
				}
				
				$pedacos = $dadosfinais;
				
				$criamatriculas = "CREATE TABLE IF NOT EXISTS `matriculas_$user` (nome VARCHAR(50) NOT NULL, matricula VARCHAR(50) NOT NULL)ENGINE=CSV;";
				mysqli_query($conexao, $criamatriculas);
				
				$checkn = "SELECT matricula FROM `matriculas_$user` WHERE matricula = '$pedacos[1]'";
				$sqlcheckn = mysqli_query($conexao, $checkn);
				$rowsn = mysqli_num_rows($sqlcheckn);
				
				$checkn2 = "SELECT matricula FROM `matriculas_$user` WHERE matricula = '$pedacos[1]'";
				$sqlcheckn2 = mysqli_query($conexao, $checkn2);
				$rowsn2 = mysqli_num_rows($sqlcheckn2);

				if ($rowsn == 0){
					if ($rowsn2 == 0){
						$string_sql = "INSERT INTO `matriculas_$user` (nome, matricula) VALUES ('$pedacos[0]','$pedacos[1]')"; // String SQL
						mysqli_query($conexao, $string_sql);
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Matrícula adicionada!'));
					}
				}
				else {
						sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Essa matrícula já foi adicionada!'));
				}
			}
			
			// Mostra modelo de adição de matrícula
			if ((strpos($text, "/nova matricula") === 0) AND $text === "/nova matricula") {
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Nova Matrícula
Nome: 
Matrícula:'));
			}
			
			// Mostra todas as matrículas
			if ((strpos($text, "/matriculas") === 0) AND $text === "/matriculas") {
				$provas = mysqli_query($conexao, "SELECT * FROM `matriculas_$user`");
				
				$mensagem = 'Suas matrículas salvas são as seguintes:
';
				while($exibe = mysqli_fetch_assoc($provas)){
					$linha = '* '.$exibe['nome'].' - '.$exibe['matricula'].'
';
					$mensagem = $mensagem.$linha;
				}
				sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem));
			}
			
			// Remove uma matrícula
			if ((strpos($text, "/delmatricula") === 0) AND $text != "/delmatricula") {
				$copiatexto = $text;
				$pedacos = explode("/delmatricula", $copiatexto);
				array_shift($pedacos);
				$pedacos = explode(" - ", $pedacos[0]);
				array_shift($pedacos);

				$string_sql = "DELETE FROM `matriculas_$user` WHERE nome = '$pedacos[0]' AND matricula = '$pedacos[1]'"; // String SQL

				$sqlcheckn= mysqli_query($conexao, $string_sql);
				$rowsn = mysqli_affected_rows($conexao);
			
				if ($rowsn > 0) {
					sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Matrícula removida!'));
				}
				else {
					sendMessage("sendMessage", array('chat_id' => $chat_id,
"text" =>'Corrija seu comando, tem alguma coisa errada!
Para obter ajuda use "/ajuda".'));
				}
			}
		}
		
		else {
			sendMessage("sendMessage", array('chat_id' => $chat_id, "text" => 'Desculpe, mas não entendi essa mensagem. :('));
		}
	}
	
	function sendMessage($method, $parameters) {
		$options = array('http' =>
			array('method' => 'POST', 'content' => json_encode($parameters), 'header'=> "Content-Type: application/json\r\n" . "Accept: application/json\r\n"));
	
		$context = stream_context_create($options);
		global $parameters;
		file_get_contents(API_URL.$method, false, $context);
	}

	/* Com o webhook setado, não precisamos mais obter as mensagens através do método getUpdates.Em vez disso, como o este arquivo será chamado automaticamente quando o bot receber uma mensagem, utilizamos "php://input" para obter o conteúdo da última mensagem enviada ao bot. */
	$update_response = file_get_contents("php://input");
	$update = json_decode($update_response, true);
	
	if (isset($update["message"])) {
		processMessage($update["message"],null);
	}
	else{
		processMessage($update["callback_query"]["message"], $update["callback_query"]["data"]);
	}
?>
