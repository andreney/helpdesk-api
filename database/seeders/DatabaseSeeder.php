<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Dados de demonstração: um usuário por perfil (senha "password"),
 * categorias e tickets em todos os status. Pode ser executado várias vezes.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Seeder de demonstração ignorado em produção.');

            return;
        }

        $users = $this->seedUsers();
        $categories = $this->seedCategories();

        $this->seedTickets($users, $categories);
    }

    /**
     * @return array<string, User> Usuários indexados por perfil (ADMIN, AGENT, USER).
     */
    private function seedUsers(): array
    {
        $users = [];

        $accounts = [
            ['Ana Administradora', 'admin@helpdesk.test', UserRole::ADMIN],
            ['Carlos Agente', 'agente@helpdesk.test', UserRole::AGENT],
            ['Marina Usuária', 'usuario@helpdesk.test', UserRole::USER],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $users[$role->name] = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'role' => $role]
            );
        }

        return $users;
    }

    /**
     * @return array<string, Category> Categorias indexadas pelo nome.
     */
    private function seedCategories(): array
    {
        $categories = [];

        $items = [
            ['Acesso e senhas', 'Contas, permissões e redefinição de senha', true],
            ['Hardware', 'Computadores, impressoras e periféricos', true],
            ['Software', 'Instalação e problemas em aplicativos', true],
            ['Rede e internet', 'Wi-Fi, VPN e conectividade', true],
            ['Sistema legado', 'Categoria desativada (não aceita novos tickets)', false],
        ];

        foreach ($items as [$name, $description, $active]) {
            $categories[$name] = Category::updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'active' => $active]
            );
        }

        return $categories;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Category>  $categories
     */
    private function seedTickets(array $users, array $categories): void
    {
        if (Ticket::query()->exists()) {
            return;
        }

        $requester = $users[UserRole::USER->name];
        $admin = $users[UserRole::ADMIN->name];
        $agent = $users[UserRole::AGENT->name];

        $tickets = [
            // [solicitante, categoria, título, descrição, prioridade, status, responsável, dias atrás, comentários]
            [$requester, 'Acesso e senhas', 'Não consigo acessar meu e-mail', 'Aparece "senha incorreta" mesmo após redefinir a senha.', TicketPriority::HIGH, TicketStatus::OPEN, null, 1, []],
            [$requester, 'Rede e internet', 'VPN desconecta a cada 10 minutos', 'Acontece apenas quando trabalho de casa.', TicketPriority::MEDIUM, TicketStatus::OPEN, null, 2, []],
            [$requester, 'Hardware', 'Impressora do 2º andar parada', 'A fila de impressão trava e nenhum documento sai.', TicketPriority::URGENT, TicketStatus::IN_PROGRESS, $agent, 3, [
                [$agent, 'Estou verificando o servidor de impressão.'],
                [$requester, 'Obrigada! Aguardo retorno.'],
            ]],
            [$admin, 'Software', 'Instalar o pacote de design', 'Preciso da licença para o time de marketing.', TicketPriority::LOW, TicketStatus::IN_PROGRESS, $agent, 4, [
                [$agent, 'Licença solicitada ao fornecedor.'],
            ]],
            [$requester, 'Software', 'Atualização do sistema travou', 'A atualização parou em 40% e o computador reiniciou.', TicketPriority::MEDIUM, TicketStatus::WAITING, $agent, 6, [
                [$agent, 'Pode reiniciar e me avisar se o erro continua?'],
            ]],
            [$requester, 'Acesso e senhas', 'Liberar acesso à pasta compartilhada', 'Preciso acessar a pasta do financeiro.', TicketPriority::LOW, TicketStatus::CLOSED, $agent, 10, [
                [$agent, 'Acesso liberado. Pode confirmar?'],
                [$requester, 'Funcionando, obrigada!'],
            ]],
            [$requester, 'Hardware', 'Troca de mouse', 'O botão esquerdo do mouse não responde.', TicketPriority::LOW, TicketStatus::CLOSED, $agent, 14, []],
        ];

        foreach ($tickets as [$owner, $category, $title, $description, $priority, $status, $assignee, $daysAgo, $comments]) {
            $createdAt = now()->subDays($daysAgo);

            $ticket = Ticket::create([
                'user_id' => $owner->id,
                'category_id' => $categories[$category]->id,
                'assigned_to' => $assignee?->id,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'status' => $status,
                'closed_at' => $status === TicketStatus::CLOSED ? $createdAt->copy()->addDay() : null,
            ]);

            $ticket->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

            foreach ($comments as [$author, $text]) {
                TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $author->id,
                    'comment' => $text,
                ]);
            }
        }
    }
}
