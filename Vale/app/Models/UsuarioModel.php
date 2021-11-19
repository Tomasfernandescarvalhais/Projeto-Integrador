<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Token;

class UsuarioModel extends Model {

    protected $table = 'usuarios';
    protected $returnType = 'App\Entities\Usuario';
    protected $allowedFields = ['nome', 'email', 'telefone', 'cpf', 'reset_hash', 'reset_expira_em', 'password', 'ativacao_hash'];
    protected $useTimestamps = true;
    protected $createdField = 'criado_em';
    protected $updatedField = 'atualizado_em';
    protected $dateFormat = 'datetime';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deletado_em';
    protected $validationRules = [
        'nome' => 'required|min_length[4]|max_length[120]',
        'email' => 'required|valid_email|is_unique[usuarios.email]',
        'cpf' => 'required|exact_length[14]|validaCpf|is_unique[usuarios.cpf]',
        'telefone' => 'required',
        'password' => 'required|min_length[6]',
        'password_confirmation' => 'required_with[password]|matches[password]',
    ];
    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Desculpe, E-mail em uso.',
            'required' => 'Campo Email Obrigatorio.',
        ],
        'cpf' => [
            'is_unique' => 'Desculpe, CPF em uso.',
            'required' => 'Campo CPF Obrigatorio.',
        ],
        'nome' => [
            'required' => 'Campo Nome Obrigatorio.',
        ],
    ];
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data) {
        if (isset($data['data']['password'])) {
            $data ['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);

            unset($data['data']['password']);
            unset($data['data']['password_confirmation']);
        }
        return $data;
    }

    public function procurar($term) {
        if ($term === null) {
            return [];
        }

        return $this->select('id,nome')
                        ->like('nome', $term)
                        ->get()
                        ->getResult();
    }

    public function desabilitarValidacaoSenha() {

        unset($this->validationRules['password']);
        unset($this->validationRules['password_confirmation']);
    }

    public function desfazerExclusao(int $id) {

        return $this->protect(false)->where('id', $id)->set('deletado_em', null)->update();
    }

    public function buscaUsuarioPorEmail(string $email) {
        return $this->where('email', $email)->first();
    }

    public function buscaUsuarioParaResetarSenha(string $token) {
        $token = new Token($token);
        $tokenHash = $token->getHash();
        $usuario = $this->where('reset_hash', $tokenHash)->first();
        if ($usuario != null) {
            if ($usuario->reset_expira_em < date('Y-m-d H:i:s')) {
                $usuario = null;
            }
            return $usuario;
        }
    }

    public function ativarContaPeloToken(string $token) {
        $token = new Token($token);
        $token_hash = $token->getHash();
        $usuario = $this->where('ativacao_hash', $token_hash)->first();
        if ($usuario != null) {
            $usuario->ativar();
            $this->protect(false)->save($usuario);
        }
    }

}
