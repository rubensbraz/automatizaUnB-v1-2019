# Automatiza UnB

O Automatiza UnB é um bot criado em PHP para gerenciar a vida universitária dos estudantes da UnB. Ele é totalmente manual - sem integração com nenhum sistema da universidade (tentarei conseguir um jeito!) - e é capaz de salvar sua grade, provas, matrículas de amigos, locais de aulas...

A licença do código é MIT - isso signifca que você pode copiar e redistribuir, sendo necessário apenas que você mantenha a cópia da licença nos seus repositórios e <b>coloque em algum lugar do seu BOT links para o AutomatizaUnB!</b>

Para ver o BOT em funcionamento, procure por @automatizaunbbot no Telegram!

"Uma ratazana de esgoto sempre ajuda outra ratazana de esgoto."

## Começando

As instruções abaixo servem para você iniciar o seu projeto!

### Pré-Requisitos

* [Hospedagem] - algum lugar na internet para hospedar seu código automatiza.php
* [Telegram] - claro, é um bot pro Telegram!

### Instalando

#### Criar bot no telegram
O primeiro passa é criar um bot no Telegram, para isso procure pelo '@BotFather' e use o comando '/newbot'. Siga as instruções de lá e anote o Token gerado!

#### Criar banco de dados
Você também vai precisar de um banco de dados para armazenar as informações geradas pelos usuários. Mude na linha 181 {DATABASE} e {PASS} pelos identificadores corretos do seu banco. Além disso, crie a uma tabela chamada "user" - sem as aspas, claro! Essa tabela terá as seguintes colunas:

#	/ Nome / Tipo</br>
1 /	id / bigint(20)</br>
2 /	first_name / char(255)</br>
3	/ created_at / timestamp</br>
4	/ saldo	/ char(255)</br>

#### 'Setar' Webhook
Depois do bot criado, coloque seu arquivo automatiza.php no seu servidor.
Agora vamos definir o link do webhook - isso nada mais é do que uma indicação para o Telegram executar seu código cada vez que o seu BOT receber uma mensagem.
O comando para setar é o abaixo; substitua as informações entre chaves pelas corretas.
```
https://api.telegram.org/bot{TOKEN}/setwebhook?url={LINK DO AUTOMATIZA.PHP}
```

#### Comandos
Para definir os comandos que aparecem no seu bot, use a opção '/setcommands' no chat com o '@BotFather'.
Os comandos usados no meu projeto estão no arquivo 'comandos.txt'

#### Começando
Mude na terceira linha do código 'automatiza.php' o {TOKEN} pelo seu próprio Token!

Agora tá tudo pronto para começar. Comece a mandar mensagens para seu BOT!

Qualquer dúvida, estou a disposição para responder:</br>
Site: www.rubensbraz.com</br>
Telegram: @rubensbraz</br>
Email: contato@rubensbraz.com

## Links Importantes
* [Telegram BOT API](https://core.telegram.org/bots/api)
* [PHP Telegram Bot](https://github.com/php-telegram-bot/core)

## Licença

O projeto usa a Licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes.
