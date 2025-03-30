use std::ffi::{CStr, CString, c_char};
use std::fs::File;
use std::io::{BufRead, BufReader};

use anyhow::{Error, anyhow};
use base64::Engine;
use ffi_helpers::{null_pointer_check, take_last_error, update_last_error};
use rustc_hash::FxHashMap as HashMap;
use tiktoken_rs::{CoreBPE, Rank};

pub struct BPE;

#[repr(C)]
pub struct Tokens {
    data: *mut u32,
    len: usize,
}

#[allow(clippy::missing_safety_doc)]
#[unsafe(no_mangle)]
pub unsafe extern "C" fn last_error_message() -> *const c_char {
    let last_error = match take_last_error() {
        Some(err) => err,
        None => return std::ptr::null_mut(),
    };

    CString::new(last_error.to_string()).unwrap().into_raw()
}

/// # Safety
/// Returned value must be freed by `destroy()`.
#[unsafe(no_mangle)]
pub unsafe extern "C" fn init(pat: *const c_char, bpe_path: *const c_char) -> *mut BPE {
    if pat.is_null() {
        update_last_error(anyhow!("The 'pat' argument is required"));

        return std::ptr::null_mut();
    }

    if bpe_path.is_null() {
        update_last_error(anyhow!("The 'bpe_path' argument is required"));

        return std::ptr::null_mut();
    }

    let pat = unsafe { CStr::from_ptr(pat) }.to_str().unwrap();
    let bpe_path = unsafe { CStr::from_ptr(bpe_path) }.to_str().unwrap();

    let file = File::open(bpe_path).unwrap_or_else(|_| panic!("Could not open file {}", bpe_path));
    let mut reader = BufReader::new(file);
    let encoder = parse_vocab(&mut reader).unwrap();

    let bpe = CoreBPE::new(encoder, HashMap::default(), pat);

    Box::into_raw(Box::new(bpe)) as *mut BPE
}

/// # Safety
/// `bpe_ref` must be valid object from `init()`.
#[unsafe(no_mangle)]
pub unsafe extern "C" fn destroy(bpe_ref: *mut BPE) {
    if !bpe_ref.is_null() {
        unsafe {
            let bpe = bpe_ref as *mut CoreBPE;
            let _ = Box::from_raw(bpe);
        }
    }
}

/// # Safety
/// `bpe_ref` must be valid object from `init()`.
/// Returned value must be freed by `free_tokens()`.
#[unsafe(no_mangle)]
pub unsafe extern "C" fn encode(bpe_ref: *mut BPE, text: *const c_char) -> *mut Tokens {
    null_pointer_check!(bpe_ref);

    let text = unsafe { CStr::from_ptr(text) }.to_str().unwrap();

    let bpe = bpe_ref as *mut CoreBPE;
    let mut tokens = unsafe { (*bpe).encode_ordinary(text) };

    let tokens_len = tokens.len();
    let ptr = tokens.as_mut_ptr();
    std::mem::forget(tokens);

    Box::into_raw(Box::new(Tokens {
        data: ptr,
        len: tokens_len,
    }))
}

/// # Safety
/// `tokens` is vector of u32 with a length of `len`.
#[unsafe(no_mangle)]
pub unsafe extern "C" fn decode(bpe_ref: *mut BPE, tokens: *const u32, len: usize) -> *const c_char {
    null_pointer_check!(bpe_ref);

    let v = unsafe { std::slice::from_raw_parts(tokens, len) }.to_vec();

    let bpe = bpe_ref as *mut CoreBPE;
    let text = unsafe { (*bpe).decode(v) }.unwrap();

    CString::new(text).unwrap().into_raw()
}

/// # Safety
/// `tokens` is result from `decode()`.
#[unsafe(no_mangle)]
pub unsafe extern "C" fn free_tokens(tokens: *mut Tokens) {
    null_pointer_check!(tokens);

    unsafe {
        let _ = Vec::from_raw_parts((*tokens).data, (*tokens).len, (*tokens).len);
    }
}

fn parse_vocab<T: BufRead>(reader: &mut T) -> Result<HashMap<Vec<u8>, Rank>, Error> {
    let b64_engine = base64::engine::general_purpose::STANDARD;
    let mut encoder = HashMap::default();
    let mut line = String::new();

    while reader.read_line(&mut line)? > 0 {
        if line.trim() != "" {
            let parts: Vec<&str> = line.trim().split(" ").collect();
            if parts.len() != 2 {
                return Err(anyhow!("unexpected line format: {}", line));
            }
            let token = b64_engine.decode(parts[0])?;
            let rank: Rank = parts[1].parse()?;
            encoder.insert(token.clone(), rank);
        }
        line.clear();
    }

    Ok(encoder)
}

#[cfg(test)]
mod test {
    use super::*;
    use std::env;
    use std::ffi::CString;
    use std::path::Path;

    #[test]
    fn test_init_and_destroy() {
        let bpe_path = Path::new(&env::current_dir().unwrap()).join("tests/Fixtures/p50k_base.tiktoken");
        let c_pat = CString::new(".+").unwrap();
        let c_bpe_path = CString::new(bpe_path.to_str().unwrap()).unwrap();

        let bpe = unsafe { init(c_pat.as_ptr(), c_bpe_path.as_ptr()) };
        assert!(!bpe.is_null());
        unsafe { destroy(bpe) };
    }
}
