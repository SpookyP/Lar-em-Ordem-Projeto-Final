<?php

namespace Database\Seeders;

use App\Models\ServiceProvider\ServiceZone;
use Illuminate\Database\Seeder;

class ServiceZoneSeeder extends Seeder
{
    /**
     * Catálogo completo de zonas: distrito → concelho → localidades.
     * Cobre os 18 distritos do continente + Açores e Madeira.
     */
    public function run(): void
    {
        $zones = [

            // ─────────────────────────────────────────
            // AVEIRO
            // ─────────────────────────────────────────
            'Aveiro' => [
                'Aveiro'                  => ['Aveiro', 'Esgueira', 'Glória', 'Vera Cruz'],
                'Águeda'                  => ['Águeda', 'Aguada de Cima', 'Borralha', 'Fermentelos'],
                'Albergaria-a-Velha'      => ['Albergaria-a-Velha', 'Branca', 'Angeja'],
                'Anadia'                  => ['Anadia', 'Avelãs de Cima', 'Paredes do Bairro'],
                'Arouca'                  => ['Arouca', 'Alvarenga', 'São Miguel do Mato'],
                'Castelo de Paiva'        => ['Castelo de Paiva', 'Sobrado'],
                'Espinho'                 => ['Espinho', 'Silvalde'],
                'Estarreja'               => ['Estarreja', 'Avanca', 'Beduído', 'Salreu'],
                'Ílhavo'                  => ['Ílhavo', 'Vista Alegre', 'Gafanha da Nazaré', 'Gafanha da Encarnação'],
                'Mealhada'                => ['Mealhada', 'Pampilhosa', 'Antes'],
                'Murtosa'                 => ['Murtosa', 'Bunheiro', 'Torreira'],
                'Oliveira de Azeméis'     => ['Oliveira de Azeméis', 'Cucujães', 'São Roque', 'Ul'],
                'Oliveira do Bairro'      => ['Oliveira do Bairro', 'Oiã', 'Palhaça'],
                'Ovar'                    => ['Ovar', 'Furadouro', 'São João', 'Cortegaça', 'Maceda'],
                'Santa Maria da Feira'    => ['Santa Maria da Feira', 'Fiães', 'Lourosa', 'Espargo', 'Mozelos', 'Paços de Brandão'],
                'São João da Madeira'     => ['São João da Madeira'],
                'Sever do Vouga'          => ['Sever do Vouga', 'Couto de Esteves'],
                'Vagos'                   => ['Vagos', 'Calvão', 'Sosa'],
                'Vale de Cambra'          => ['Vale de Cambra', 'Arões', 'Macieira de Cambra'],
            ],

            // ─────────────────────────────────────────
            // BEJA
            // ─────────────────────────────────────────
            'Beja' => [
                'Aljustrel'               => ['Aljustrel', 'Messejana'],
                'Almodôvar'               => ['Almodôvar', 'Santa Clara-a-Nova'],
                'Alvito'                  => ['Alvito'],
                'Barrancos'               => ['Barrancos'],
                'Beja'                    => ['Beja', 'Salvador', 'Santiago Maior', 'Santa Maria da Feira'],
                'Castro Verde'            => ['Castro Verde', 'Almodôvar'],
                'Cuba'                    => ['Cuba'],
                'Ferreira do Alentejo'    => ['Ferreira do Alentejo', 'Figueira dos Cavaleiros'],
                'Mértola'                 => ['Mértola', 'Alcoutim'],
                'Moura'                   => ['Moura', 'Safara', 'Santo Amador'],
                'Odemira'                 => ['Odemira', 'São Teotónio', 'Vila Nova de Milfontes', 'Longueira'],
                'Ourique'                 => ['Ourique', 'Garvão'],
                'Serpa'                   => ['Serpa', 'Pias', 'Brinches'],
                'Vidigueira'              => ['Vidigueira', 'Pedrógão do Alentejo'],
            ],

            // ─────────────────────────────────────────
            // BRAGA
            // ─────────────────────────────────────────
            'Braga' => [
                'Amares'                  => ['Amares', 'Sequeiros', 'Caldelas'],
                'Barcelos'                => ['Barcelos', 'Barcelinhos', 'Viatodos', 'Macieira de Rates'],
                'Braga'                   => ['Braga', 'São Vicente', 'Maximinos', 'Priscos', 'Nogueira', 'Ferreiros', 'Gualtar'],
                'Cabeceiras de Basto'     => ['Cabeceiras de Basto', 'Bucos'],
                'Celorico de Basto'       => ['Celorico de Basto', 'Fervença'],
                'Esposende'               => ['Esposende', 'Apúlia', 'Marinhas', 'Fão'],
                'Fafe'                    => ['Fafe', 'Fornelos', 'Cepães'],
                'Guimarães'               => ['Guimarães', 'Azurém', 'Serzedo', 'Creixomil', 'Lordelo'],
                'Póvoa de Lanhoso'        => ['Póvoa de Lanhoso', 'Fontarcada'],
                'Terras de Bouro'         => ['Gerês', 'Campo do Gerês', 'Vilar da Veiga'],
                'Vieira do Minho'         => ['Vieira do Minho', 'Ruivães'],
                'Vila Nova de Famalicão'  => ['Vila Nova de Famalicão', 'Riba de Ave', 'Nine', 'Calendário'],
                'Vila Verde'              => ['Vila Verde', 'Prado', 'Arentim'],
                'Vizela'                  => ['Vizela', 'Infias', 'Tagilde'],
            ],

            // ─────────────────────────────────────────
            // BRAGANÇA
            // ─────────────────────────────────────────
            'Bragança' => [
                'Alfândega da Fé'         => ['Alfândega da Fé'],
                'Bragança'                => ['Bragança', 'Sé', 'Samil'],
                'Carrazeda de Ansiães'    => ['Carrazeda de Ansiães', 'Vilarinho da Castanheira'],
                'Freixo de Espada à Cinta'=> ['Freixo de Espada à Cinta'],
                'Macedo de Cavaleiros'    => ['Macedo de Cavaleiros', 'Morais'],
                'Miranda do Douro'        => ['Miranda do Douro', 'Sendim'],
                'Mirandela'               => ['Mirandela', 'Frechas'],
                'Mogadouro'               => ['Mogadouro', 'Azinhoso'],
                'Torre de Moncorvo'       => ['Torre de Moncorvo', 'Moncorvo'],
                'Vila Flor'               => ['Vila Flor'],
                'Vimioso'                 => ['Vimioso'],
                'Vinhais'                 => ['Vinhais'],
            ],

            // ─────────────────────────────────────────
            // CASTELO BRANCO
            // ─────────────────────────────────────────
            'Castelo Branco' => [
                'Belmonte'                => ['Belmonte', 'Caria'],
                'Castelo Branco'          => ['Castelo Branco', 'Alcains', 'Cebolais de Cima'],
                'Covilhã'                 => ['Covilhã', 'Tortosendo', 'Ferro', 'Boidobra'],
                'Fundão'                  => ['Fundão', 'Alcongosta', 'Alcaide'],
                'Idanha-a-Nova'           => ['Idanha-a-Nova', 'Monsanto', 'Penha Garcia'],
                'Oleiros'                 => ['Oleiros'],
                'Penamacor'               => ['Penamacor'],
                'Proença-a-Nova'          => ['Proença-a-Nova'],
                'Sertã'                   => ['Sertã', 'Cernache do Bonjardim'],
                'Vila de Rei'             => ['Vila de Rei'],
                'Vila Velha de Ródão'     => ['Vila Velha de Ródão'],
            ],

            // ─────────────────────────────────────────
            // COIMBRA
            // ─────────────────────────────────────────
            'Coimbra' => [
                'Arganil'                 => ['Arganil', 'Côja'],
                'Cantanhede'              => ['Cantanhede', 'Ançã', 'Murtede'],
                'Coimbra'                 => ['Coimbra', 'Santa Clara', 'Sé Nova', 'São Martinho do Bispo', 'Eiras', 'Ceira'],
                'Condeixa-a-Nova'         => ['Condeixa-a-Nova'],
                'Figueira da Foz'         => ['Figueira da Foz', 'Buarcos', 'Tavarede', 'Lavos'],
                'Góis'                    => ['Góis'],
                'Lousã'                   => ['Lousã'],
                'Mira'                    => ['Mira', 'Praia de Mira'],
                'Miranda do Corvo'        => ['Miranda do Corvo', 'Lousã'],
                'Montemor-o-Velho'        => ['Montemor-o-Velho', 'Abrunheira', 'Tentúgal'],
                'Oliveira do Hospital'    => ['Oliveira do Hospital', 'Seixo da Beira'],
                'Pampilhosa da Serra'     => ['Pampilhosa da Serra'],
                'Penacova'                => ['Penacova', 'Lorvão'],
                'Penela'                  => ['Penela'],
                'Soure'                   => ['Soure'],
                'Tábua'                   => ['Tábua'],
                'Vila Nova de Poiares'    => ['Vila Nova de Poiares'],
            ],

            // ─────────────────────────────────────────
            // ÉVORA
            // ─────────────────────────────────────────
            'Évora' => [
                'Alandroal'               => ['Alandroal', 'Juromenha'],
                'Arraiolos'               => ['Arraiolos', 'Igrejinha'],
                'Borba'                   => ['Borba'],
                'Estremoz'                => ['Estremoz', 'Santo André'],
                'Évora'                   => ['Évora', 'Malagueira', 'Horta das Figueiras'],
                'Montemor-o-Novo'         => ['Montemor-o-Novo', 'Santiago do Escoural'],
                'Mora'                    => ['Mora'],
                'Mourão'                  => ['Mourão'],
                'Portel'                  => ['Portel'],
                'Redondo'                 => ['Redondo'],
                'Reguengos de Monsaraz'   => ['Reguengos de Monsaraz', 'Monsaraz'],
                'Vendas Novas'            => ['Vendas Novas'],
                'Viana do Alentejo'       => ['Viana do Alentejo', 'Alvito'],
                'Vila Viçosa'             => ['Vila Viçosa'],
            ],

            // ─────────────────────────────────────────
            // FARO (ALGARVE)
            // ─────────────────────────────────────────
            'Faro' => [
                'Albufeira'               => ['Albufeira', 'Guia', 'Ferreiras', 'Paderne', 'Olhos de Água'],
                'Alcoutim'                => ['Alcoutim', 'Martim Longo'],
                'Aljezur'                 => ['Aljezur', 'Odeceixe'],
                'Castro Marim'            => ['Castro Marim', 'Altura'],
                'Faro'                    => ['Faro', 'Montenegro', 'São Pedro', 'Santa Bárbara de Nexe'],
                'Lagoa'                   => ['Lagoa', 'Carvoeiro', 'Porches', 'Estômbar'],
                'Lagos'                   => ['Lagos', 'Luz', 'Meia Praia', 'Odiáxere'],
                'Loulé'                   => ['Loulé', 'Almancil', 'Quarteira', 'Vilamoura', 'Benafim'],
                'Monchique'               => ['Monchique', 'Alferce', 'Caldas de Monchique'],
                'Olhão'                   => ['Olhão', 'Fuseta', 'Quelfes', 'Moncarapacho'],
                'Portimão'                => ['Portimão', 'Alvor', 'Mexilhoeira Grande'],
                'São Brás de Alportel'    => ['São Brás de Alportel'],
                'Silves'                  => ['Silves', 'Armação de Pêra', 'Algoz', 'São Bartolomeu de Messines'],
                'Tavira'                  => ['Tavira', 'Cabanas de Tavira', 'Santa Luzia', 'Luz de Tavira'],
                'Vila do Bispo'           => ['Vila do Bispo', 'Sagres', 'Salema'],
                'Vila Real de Santo António' => ['Vila Real de Santo António', 'Monte Gordo', 'Castro Marim'],
            ],

            // ─────────────────────────────────────────
            // GUARDA
            // ─────────────────────────────────────────
            'Guarda' => [
                'Aguiar da Beira'         => ['Aguiar da Beira'],
                'Almeida'                 => ['Almeida', 'Vilar Formoso'],
                'Celorico da Beira'       => ['Celorico da Beira'],
                'Figueira de Castelo Rodrigo' => ['Figueira de Castelo Rodrigo', 'Castelo Rodrigo'],
                'Fornos de Algodres'      => ['Fornos de Algodres'],
                'Gouveia'                 => ['Gouveia', 'Melo'],
                'Guarda'                  => ['Guarda', 'Sé', 'São Miguel'],
                'Manteigas'               => ['Manteigas'],
                'Mêda'                    => ['Mêda'],
                'Pinhel'                  => ['Pinhel'],
                'Sabugal'                 => ['Sabugal', 'Aldeia da Ponte'],
                'Seia'                    => ['Seia', 'Sabugueiro'],
                'Trancoso'                => ['Trancoso'],
                'Vila Nova de Foz Côa'    => ['Vila Nova de Foz Côa'],
            ],

            // ─────────────────────────────────────────
            // LEIRIA
            // ─────────────────────────────────────────
            'Leiria' => [
                'Alcobaça'                => ['Alcobaça', 'Alfeizerão', 'Turquel'],
                'Alvaiázere'              => ['Alvaiázere'],
                'Ansião'                  => ['Ansião'],
                'Batalha'                 => ['Batalha', 'Porto de Mós'],
                'Bombarral'               => ['Bombarral'],
                'Caldas da Rainha'        => ['Caldas da Rainha', 'Tornada', 'Nadadouro'],
                'Castanheira de Pêra'     => ['Castanheira de Pêra'],
                'Figueiró dos Vinhos'     => ['Figueiró dos Vinhos'],
                'Leiria'                  => ['Leiria', 'Marrazes', 'Pousos', 'Barosa', 'Maceira'],
                'Marinha Grande'          => ['Marinha Grande', 'Vieira de Leiria'],
                'Nazaré'                  => ['Nazaré', 'Famalicão', 'Valado dos Frades'],
                'Óbidos'                  => ['Óbidos', 'A-dos-Negros'],
                'Pedrógão Grande'         => ['Pedrógão Grande'],
                'Peniche'                 => ['Peniche', 'Atouguia da Baleia', 'Ferrel'],
                'Pombal'                  => ['Pombal', 'Abiul'],
                'Porto de Mós'            => ['Porto de Mós', 'Alqueidão da Serra'],
            ],

            // ─────────────────────────────────────────
            // LISBOA
            // ─────────────────────────────────────────
            'Lisboa' => [
                'Alenquer'                => ['Alenquer', 'Cadafais', 'Triana'],
                'Amadora'                 => ['Amadora', 'Alfragide', 'Venteira', 'Mina de Água', 'Falagueira'],
                'Arruda dos Vinhos'       => ['Arruda dos Vinhos'],
                'Azambuja'                => ['Azambuja', 'Aveiras de Cima', 'Aveiras de Baixo'],
                'Cadaval'                 => ['Cadaval'],
                'Cascais'                 => ['Cascais', 'Estoril', 'Parede', 'Alcabideche', 'Birre', 'São Domingos de Rana'],
                'Lisboa'                  => ['Lisboa', 'Alfama', 'Belém', 'Chiado', 'Mouraria', 'Parque das Nações', 'Restelo', 'Lumiar', 'Benfica', 'Carnide'],
                'Loures'                  => ['Loures', 'Sacavém', 'Prior Velho', 'Camarate', 'Santo António dos Cavaleiros', 'Apelação'],
                'Lourinhã'                => ['Lourinhã', 'Ribamar'],
                'Mafra'                   => ['Mafra', 'Ericeira', 'Malveira', 'Venda do Pinheiro'],
                'Odivelas'                => ['Odivelas', 'Pontinha', 'Famões', 'Ramada', 'Caneças'],
                'Oeiras'                  => ['Oeiras', 'Paço de Arcos', 'Algés', 'Linda-a-Velha', 'Porto Salvo', 'Barcarena'],
                'Sintra'                  => ['Sintra', 'Agualva-Cacém', 'Queluz', 'Rio de Mouro', 'Massamá', 'Monte Abraão', 'Mem Martins'],
                'Sobral de Monte Agraço'  => ['Sobral de Monte Agraço'],
                'Torres Vedras'           => ['Torres Vedras', 'Campelos', 'Dois Portos', 'Silveira'],
                'Vila Franca de Xira'     => ['Vila Franca de Xira', 'Alverca do Ribatejo', 'Alhandra', 'Póvoa de Santa Iria', 'Forte da Casa'],
            ],

            // ─────────────────────────────────────────
            // PORTALEGRE
            // ─────────────────────────────────────────
            'Portalegre' => [
                'Alter do Chão'           => ['Alter do Chão'],
                'Arronches'               => ['Arronches'],
                'Avis'                    => ['Avis'],
                'Campo Maior'             => ['Campo Maior'],
                'Castelo de Vide'         => ['Castelo de Vide'],
                'Crato'                   => ['Crato', 'Flor da Rosa'],
                'Elvas'                   => ['Elvas', 'Caia e São Pedro', 'Varche'],
                'Fronteira'               => ['Fronteira'],
                'Gavião'                  => ['Gavião'],
                'Marvão'                  => ['Marvão'],
                'Monforte'                => ['Monforte'],
                'Nisa'                    => ['Nisa'],
                'Ponte de Sor'            => ['Ponte de Sor'],
                'Portalegre'              => ['Portalegre', 'Sé', 'São Lourenço', 'Urra'],
                'Sousel'                  => ['Sousel'],
            ],

            // ─────────────────────────────────────────
            // PORTO
            // ─────────────────────────────────────────
            'Porto' => [
                'Amarante'                => ['Amarante', 'Fregim', 'Louredo'],
                'Baião'                   => ['Baião', 'Campelo'],
                'Felgueiras'              => ['Felgueiras', 'Lixa', 'Moure'],
                'Gondomar'                => ['Gondomar', 'Rio Tinto', 'Baguim do Monte', 'Valbom', 'Fanzeres'],
                'Lousada'                 => ['Lousada', 'Torno', 'Nevogilde'],
                'Maia'                    => ['Maia', 'Águas Santas', 'Moreira da Maia', 'Pedrouços', 'Vermoim'],
                'Marco de Canaveses'      => ['Marco de Canaveses', 'Tabuado'],
                'Matosinhos'              => ['Matosinhos', 'Senhora da Hora', 'Leça da Palmeira', 'Leça do Balio', 'Custóias'],
                'Paços de Ferreira'       => ['Paços de Ferreira', 'Freamunde', 'Modelos'],
                'Paredes'                 => ['Paredes', 'Lordelo', 'Baltar'],
                'Penafiel'                => ['Penafiel', 'Recezinhos'],
                'Porto'                   => ['Porto', 'Paranhos', 'Campanhã', 'Bonfim', 'Cedofeita', 'Ramalde', 'Lordelo do Ouro', 'Massarelos', 'Aldoar', 'Nevogilde'],
                'Póvoa de Varzim'         => ['Póvoa de Varzim', 'Aguçadoura', 'Argivai', 'Balazar'],
                'Santo Tirso'             => ['Santo Tirso', 'São Mamede de Negrelos', 'Areias'],
                'Trofa'                   => ['Trofa', 'Covelas', 'Muro'],
                'Valongo'                 => ['Valongo', 'Ermesinde', 'Alfena', 'Campo'],
                'Vila do Conde'           => ['Vila do Conde', 'Azurara', 'Mindelo', 'Labruge'],
                'Vila Nova de Gaia'       => ['Vila Nova de Gaia', 'Canidelo', 'Mafamude', 'Oliveira do Douro', 'Arcozelo', 'Avintes', 'Sandim', 'Vilar de Andorinho'],
            ],

            // ─────────────────────────────────────────
            // SANTARÉM
            // ─────────────────────────────────────────
            'Santarém' => [
                'Abrantes'                => ['Abrantes', 'Mouriscas'],
                'Alcanena'                => ['Alcanena', 'Serra de Santo António'],
                'Almeirim'                => ['Almeirim'],
                'Alpiarça'                => ['Alpiarça'],
                'Benavente'               => ['Benavente', 'Samora Correia', 'Santo Estêvão'],
                'Cartaxo'                 => ['Cartaxo', 'Vale da Pedra'],
                'Chamusca'                => ['Chamusca'],
                'Constância'              => ['Constância'],
                'Coruche'                 => ['Coruche'],
                'Entroncamento'           => ['Entroncamento'],
                'Ferreira do Zêzere'      => ['Ferreira do Zêzere'],
                'Golegã'                  => ['Golegã'],
                'Mação'                   => ['Mação'],
                'Ourém'                   => ['Ourém', 'Fátima', 'Caxarias'],
                'Rio Maior'               => ['Rio Maior'],
                'Salvaterra de Magos'     => ['Salvaterra de Magos', 'Marinhais', 'Glória do Ribatejo'],
                'Santarém'                => ['Santarém', 'Marvila', 'Ribeira de Santarém', 'Almoster'],
                'Sardoal'                 => ['Sardoal'],
                'Tomar'                   => ['Tomar', 'Asseiceira'],
                'Torres Novas'            => ['Torres Novas', 'Riachos'],
                'Vila Nova da Barquinha'  => ['Vila Nova da Barquinha'],
            ],

            // ─────────────────────────────────────────
            // SETÚBAL
            // ─────────────────────────────────────────
            'Setúbal' => [
                'Alcácer do Sal'          => ['Alcácer do Sal', 'Santa Susana'],
                'Alcochete'               => ['Alcochete'],
                'Almada'                  => ['Almada', 'Cacilhas', 'Charneca da Caparica', 'Costa da Caparica', 'Trafaria', 'Cova da Piedade'],
                'Barreiro'                => ['Barreiro', 'Alto do Seixalinho', 'Lavradio'],
                'Grândola'                => ['Grândola', 'Melides'],
                'Moita'                   => ['Moita', 'Baixa da Banheira', 'Vale da Amoreira'],
                'Montijo'                 => ['Montijo', 'Atalaia', 'Canha'],
                'Palmela'                 => ['Palmela', 'Pinhal Novo', 'Quinta do Anjo'],
                'Santiago do Cacém'       => ['Santiago do Cacém', 'Santo André', 'Cercal do Alentejo'],
                'Seixal'                  => ['Seixal', 'Amora', 'Corroios', 'Fernão Ferro', 'Aldeia de Paio Pires'],
                'Sesimbra'                => ['Sesimbra', 'Quinta do Conde', 'Santana'],
                'Setúbal'                 => ['Setúbal', 'Azeitão', 'São Bernardo', 'Gambia-Pontes-Alto da Guerra'],
                'Sines'                   => ['Sines', 'Porto Covo'],
            ],

            // ─────────────────────────────────────────
            // VIANA DO CASTELO
            // ─────────────────────────────────────────
            'Viana do Castelo' => [
                'Arcos de Valdevez'       => ['Arcos de Valdevez', 'Giela'],
                'Caminha'                 => ['Caminha', 'Moledo', 'Vila Praia de Âncora'],
                'Melgaço'                 => ['Melgaço'],
                'Monção'                  => ['Monção', 'Lapela'],
                'Paredes de Coura'        => ['Paredes de Coura'],
                'Ponte da Barca'          => ['Ponte da Barca', 'Vila Nova de Muía'],
                'Ponte de Lima'           => ['Ponte de Lima', 'Arcozelo', 'Facha'],
                'Valença'                 => ['Valença', 'Boivão'],
                'Viana do Castelo'        => ['Viana do Castelo', 'Darque', 'Santa Maria Maior', 'Areosa', 'Meadela'],
                'Vila Nova de Cerveira'   => ['Vila Nova de Cerveira'],
            ],

            // ─────────────────────────────────────────
            // VILA REAL
            // ─────────────────────────────────────────
            'Vila Real' => [
                'Alijó'                   => ['Alijó', 'Favaios'],
                'Boticas'                 => ['Boticas'],
                'Chaves'                  => ['Chaves', 'Vilarelho da Raia', 'Santa Maria Maior'],
                'Mesão Frio'              => ['Mesão Frio'],
                'Mondim de Basto'         => ['Mondim de Basto'],
                'Montalegre'              => ['Montalegre', 'Cabril'],
                'Murça'                   => ['Murça'],
                'Peso da Régua'           => ['Peso da Régua', 'Godim'],
                'Ribeira de Pena'         => ['Ribeira de Pena'],
                'Sabrosa'                 => ['Sabrosa'],
                'Santa Marta de Penaguião'=> ['Santa Marta de Penaguião'],
                'Valpaços'                => ['Valpaços'],
                'Vila Pouca de Aguiar'    => ['Vila Pouca de Aguiar'],
                'Vila Real'               => ['Vila Real', 'Mateus', 'Constantim', 'Borbela'],
            ],

            // ─────────────────────────────────────────
            // VISEU
            // ─────────────────────────────────────────
            'Viseu' => [
                'Armamar'                 => ['Armamar'],
                'Carregal do Sal'         => ['Carregal do Sal'],
                'Castro Daire'            => ['Castro Daire'],
                'Cinfães'                 => ['Cinfães', 'Tendais'],
                'Lamego'                  => ['Lamego', 'Britiande'],
                'Mangualde'               => ['Mangualde', 'Abrunhosa-a-Velha'],
                'Moimenta da Beira'       => ['Moimenta da Beira'],
                'Mortágua'                => ['Mortágua'],
                'Nelas'                   => ['Nelas'],
                'Oliveira de Frades'      => ['Oliveira de Frades'],
                'Penalva do Castelo'      => ['Penalva do Castelo'],
                'Penedono'                => ['Penedono'],
                'Resende'                 => ['Resende'],
                'Santa Comba Dão'         => ['Santa Comba Dão'],
                'São João da Pesqueira'   => ['São João da Pesqueira'],
                'São Pedro do Sul'        => ['São Pedro do Sul', 'Termas de São Pedro do Sul'],
                'Sátão'                   => ['Sátão'],
                'Sernancelhe'             => ['Sernancelhe'],
                'Tabuaço'                 => ['Tabuaço'],
                'Tarouca'                 => ['Tarouca'],
                'Tondela'                 => ['Tondela', 'Caramulo'],
                'Vila Nova de Paiva'      => ['Vila Nova de Paiva'],
                'Viseu'                   => ['Viseu', 'Repeses', 'São José', 'Ranhados'],
                'Vouzela'                 => ['Vouzela'],
            ],

            // ─────────────────────────────────────────
            // AÇORES
            // ─────────────────────────────────────────
            'Açores' => [
                'Angra do Heroísmo'       => ['Angra do Heroísmo', 'São Mateus da Calheta', 'Altares'],
                'Calheta (Açores)'        => ['Calheta', 'Fajã Grande'],
                'Corvo'                   => ['Vila do Corvo'],
                'Flores'                  => ['Santa Cruz das Flores'],
                'Horta'                   => ['Horta', 'Feteira', 'Capelo'],
                'Lagoa (Açores)'          => ['Lagoa', 'Água de Pau'],
                'Lajes das Flores'        => ['Lajes das Flores'],
                'Lajes do Pico'           => ['Lajes do Pico', 'Ribeiras'],
                'Madalena'                => ['Madalena', 'Criação Velha'],
                'Nordeste'                => ['Nordeste'],
                'Ponta Delgada'           => ['Ponta Delgada', 'Rabo de Peixe', 'Rosto de Cão'],
                'Povoação'                => ['Povoação', 'Faial da Terra'],
                'Praia da Vitória'        => ['Praia da Vitória', 'Biscoitos'],
                'Ribeira Grande'          => ['Ribeira Grande', 'Pico da Pedra'],
                'Santa Cruz da Graciosa'  => ['Santa Cruz da Graciosa'],
                'Santa Cruz das Flores'   => ['Santa Cruz das Flores'],
                'São Roque do Pico'       => ['São Roque do Pico'],
                'Velas'                   => ['Velas'],
                'Vila do Porto'           => ['Vila do Porto'],
                'Vila Franca do Campo'    => ['Vila Franca do Campo'],
            ],

            // ─────────────────────────────────────────
            // MADEIRA
            // ─────────────────────────────────────────
            'Madeira' => [
                'Calheta'                 => ['Calheta', 'Jardim do Mar', 'Prazeres', 'Arco da Calheta'],
                'Câmara de Lobos'         => ['Câmara de Lobos', 'Curral das Freiras', 'Estreito de Câmara de Lobos'],
                'Funchal'                 => ['Funchal', 'Monte', 'Santo António', 'São Gonçalo', 'São Roque', 'Imaculado Coração de Maria'],
                'Machico'                 => ['Machico', 'Caniçal', 'Porto da Cruz', 'Santo António da Serra'],
                'Ponta do Sol'            => ['Ponta do Sol', 'Canhas', 'Madalena do Mar'],
                'Porto Moniz'             => ['Porto Moniz', 'Ribeira da Janela'],
                'Porto Santo'             => ['Vila Baleira'],
                'Ribeira Brava'           => ['Ribeira Brava', 'Serra de Água', 'Campanário'],
                'Santa Cruz'              => ['Santa Cruz', 'Caniço', 'Gaula', 'Camacha'],
                'Santana'                 => ['Santana', 'São Jorge', 'Arco de São Jorge', 'Faial'],
                'São Vicente'             => ['São Vicente', 'Ponta Delgada', 'Boaventura'],
            ],

        ];

        foreach ($zones as $district => $counties) {
            foreach ($counties as $county => $locations) {
                foreach ($locations as $location) {
                    ServiceZone::firstOrCreate([
                        'district' => $district,
                        'county'   => $county,
                        'location' => $location,
                    ]);
                }
            }
        }
    }
}