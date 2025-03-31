#define FFI_SCOPE "tiktoken"

typedef struct BPE BPE;

typedef struct {
  uint32_t *data;
  size_t len;
} Tokens;

BPE *init(const char *pat, const char *dict_path);

void destroy(BPE *bpe_ref);

Tokens *encode(BPE *bpe_ref, const char *text);

const char *decode(BPE *bpe_ref, const uint32_t *tokens, size_t len);

void free_tokens(Tokens *vec);

const char *last_error_message();
