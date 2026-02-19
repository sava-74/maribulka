const vscode = require('vscode');

class VSCodeMementoIntegration {
    constructor() {
        this.outputChannel = vscode.window.createOutputChannel('Memento Protocol');
        this.apiKey = 'mp_live_a6f04585bef67482d0629359a4d41cd0';
        this.apiUrl = 'https://memento-api.myrakrusemark.workers.dev';
    }

    async activate(context) {
        // Регистрируем команды
        let disposable = vscode.commands.registerCommand('memento.recall', async () => {
            const query = await vscode.window.showInputBox({
                prompt: 'Введите запрос для поиска воспоминаний'
            });
            
            if (query) {
                await this.handleUserPrompt(query);
            }
        });

        context.subscriptions.push(disposable);

        // Автоматический хук на ввод пользователя
        vscode.workspace.onDidChangeTextDocument(async (event) => {
            if (event.document === vscode.window.activeTextEditor?.document) {
                const text = event.document.getText();
                if (text.length > 10) {
                    await this.handleUserPrompt(text.substring(0, 100));
                }
            }
        });

        this.outputChannel.appendLine('Memento Protocol интеграция активирована');
    }

    async handleUserPrompt(userMessage) {
        if (userMessage.length < 10) return;

        try {
            const memories = await this.fetchMemories(userMessage);
            if (memories && memories.length > 0) {
                this.showRecallMessage(memories.length, memories);
            }
        } catch (error) {
            this.outputChannel.appendLine(`Ошибка: ${error.message}`);
        }
    }

    async fetchMemories(query) {
        const response = await fetch(`${this.apiUrl}/v1/context`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: query,
                include: ['memories', 'skip_list']
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        return data.memories?.matches || [];
    }

    showRecallMessage(count, memories) {
        const message = `Memento Recall: ${count} воспоминаний`;
        this.outputChannel.appendLine(message);
        
        memories.slice(0, 3).forEach(memory => {
            const tags = memory.tags ? ` [${memory.tags.join(', ')}]` : '';
            const content = memory.content.substring(0, 80);
            this.outputChannel.appendLine(`  ${memory.id} (${memory.type})${tags} — ${content}`);
        });

        vscode.window.showInformationMessage(message);
    }

    deactivate() {
        this.outputChannel.dispose();
    }
}

module.exports = VSCodeMementoIntegration;