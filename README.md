# Automatiza UnB

O Automatiza UnB é um bot criado em PHP para gerenciar a vida universitária dos estudantes da UnB. Ele é totalmente manual - sem integração com nenhum sistema da universidade (tentarei conseguir um jeito!) - e é capaz de salvar sua grade, provas, matrículas de amigos, locais de aulas...

A licença do código é MIT - isso signifca que você pode copiar e redistribuir, sendo necessário apenas que você mantenha a cópia da licença no seu código e uma função dentro do app que mostra os créditos.

Para ver o BOT em funcionamento, procure por @automatizaunbbot no Telegram!

"Uma ratazana de esgoto sempre ajuda outra ratazana de esgoto."

## Começando

As instruções abaixo servem para você iniciar o seu projeto!

### Pré-Requisitos

* [Hospedagem] - algum lugar na internet para hospedar seu código automatiza.php
* [Telegram] - claro, é um bot pro Telegram!

### Instalando

#### Criar bot no telegram
O primeiro passa é criar um bot no Telegram, para isso procure pelo @BotFather e use o comando "/newbot". Siga as instruções de lá e anote o Token gerado!

#### 'Setar' Webhook
Depois do bot criado, coloque seu arquivo automatiza.php no seu servidor.
Agora vamos definir o link do webhook - isso nada mais é do que uma indicação para o Telegram executar seu código cada vez que o seu BOT receber uma mensagem.
O comando para setar é o abaixo; substitua as informações entre chaves pelas corretas.
```
https://api.telegram.org/bot{TOKEN}/setwebhook?url={LINK DO AUTOMATIZA.PHP}
```

#### Começando
Agora tá tudo pronto para começar. Comece a mandar mensagens para seu BOT!

Qualquer dúvida, estou a disposição para responder:</br>
Site: www.rubensbraz.com</br>
Telegram: @rubensbraz</br>
Email: contato@rubensbraz.com

## Licença

O projeto usa a Licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

