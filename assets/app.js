const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
if (menuToggle && navLinks) {
  menuToggle.addEventListener("click", () => navLinks.classList.toggle("open"));
}
document.querySelectorAll('a[href^="#"]').forEach(link => {
  link.addEventListener("click", () => navLinks?.classList.remove("open"));
});

const openLibraryResults = document.getElementById("openLibraryResults");
const openLibraryMessage = document.getElementById("openLibraryMessage");
const openLibraryCount = document.getElementById("openLibraryCount");
const openLibrarySearch = document.getElementById("openLibrarySearch");
const openLibraryQuery = document.getElementById("openLibraryQuery");
const openLibraryLoadMore = document.getElementById("openLibraryLoadMore");
const bookPreview = document.getElementById("bookPreview");
const openLibraryEditionFacts = document.getElementById("openLibraryEditionFacts");
const openLibraryBatchSize = 100;
const openLibraryMaxBooks = 1000;
const openLibraryPageSize = 100;
let openLibraryPage = 1;
let openLibraryTotal = 0;
let openLibraryBooks = [];
let openLibraryKeys = new Set();
let openLibraryPendingBooks = [];
let openLibraryCurrentQuery = "education";
let openLibraryController = null;
let openLibraryRequestId = 0;

function makeOpenLibraryCard(book) {
  const card = document.createElement("article");
  card.className = "book-card";

  const coverWrap = document.createElement("div");
  coverWrap.className = "cover-wrap open-library-cover";

  if (book.cover_i) {
    const cover = document.createElement("img");
    cover.src = `https://covers.openlibrary.org/b/id/${encodeURIComponent(book.cover_i)}-M.jpg?default=false`;
    cover.alt = `Cover of ${book.title}`;
    cover.loading = "lazy";
    cover.addEventListener("error", () => {
      cover.remove();
      coverWrap.classList.add("open-library-cover-missing");
      coverWrap.append("No cover available");
    }, { once: true });
    coverWrap.append(cover);
  } else {
    coverWrap.classList.add("open-library-cover-missing");
    coverWrap.textContent = "No cover available";
  }

  const info = document.createElement("div");
  info.className = "book-info";
  const subject = document.createElement("div");
  subject.className = "book-subject";
  subject.textContent = "Open Library";
  const title = document.createElement("h3");
  title.textContent = book.title || "Untitled";
  const author = document.createElement("p");
  author.className = "book-author";
  const authors = (Array.isArray(book.author_name) ? book.author_name : [])
    .filter(name => typeof name === "string" && name.trim())
    .slice(0, 3);
  author.textContent = `by ${authors.length ? authors.join(", ") : "Unknown author"}`;
  const meta = document.createElement("div");
  meta.className = "book-meta";
  const year = document.createElement("span");
  year.className = "book-price";
  year.textContent = book.first_publish_year ? `First published ${book.first_publish_year}` : "Publication year unavailable";
  const editions = document.createElement("span");
  editions.className = "book-seller";
  editions.textContent = book.edition_count ? `${book.edition_count} editions` : "";
  meta.append(year, editions);
  const details = document.createElement("div");
  details.className = "card-link";
  details.textContent = "Catalog details from Open Library";

  const listLink = document.createElement("a");
  listLink.className = "btn btn-primary btn-small open-library-list-link";
  listLink.textContent = "List your copy";
  const subjects = Array.isArray(book.subject) ? book.subject : [];
  const listingParams = new URLSearchParams({
    title: book.title || "",
    author: authors[0] || "",
    subject: subjects.find(item => typeof item === "string" && item.length <= 100) || "General"
  });
  listLink.href = `add_book.php?${listingParams}`;

  info.append(subject, title, author, meta, details, listLink);
  card.append(coverWrap, info);
  return card;
}

async function loadOpenLibraryBooks(query) {
  if (!openLibraryResults || !openLibraryMessage || !openLibraryCount) return;
  if (openLibraryController) openLibraryController.abort();
  const controller = new AbortController();
  openLibraryController = controller;
  const requestId = ++openLibraryRequestId;
  const loadingMore = query === null;

  if (!loadingMore) {
    openLibraryCurrentQuery = query.trim() || "education";
    openLibraryPage = 1;
    openLibraryTotal = 0;
    openLibraryBooks = [];
    openLibraryKeys = new Set();
    openLibraryPendingBooks = [];
    openLibraryResults.replaceChildren();
  }
  if (openLibraryLoadMore) openLibraryLoadMore.disabled = true;
  openLibraryMessage.hidden = false;
  openLibraryMessage.textContent = loadingMore ? "Loading more books…" : "Loading books with author names…";
  if (!loadingMore) openLibraryCount.textContent = "Loading books…";
  const targetCount = Math.min(openLibraryMaxBooks, openLibraryBooks.length + openLibraryBatchSize);
  const addedBooks = [];
  let pagesFetched = 0;

  try {
    while (openLibraryPendingBooks.length && openLibraryBooks.length + addedBooks.length < targetCount) {
      addedBooks.push(openLibraryPendingBooks.shift());
    }
    while (
      openLibraryBooks.length + addedBooks.length < targetCount &&
      pagesFetched < 5 &&
      (openLibraryTotal === 0 || (openLibraryPage - 1) * openLibraryPageSize < openLibraryTotal)
    ) {
      const params = new URLSearchParams({
        q: openLibraryCurrentQuery,
        fields: "key,title,author_name,first_publish_year,cover_i,edition_count,subject",
        limit: String(openLibraryPageSize),
        page: String(openLibraryPage)
      });
      const response = await fetch(`https://openlibrary.org/search.json?${params}`, { signal: controller.signal });
      if (!response.ok) throw new Error(`Open Library returned ${response.status}`);
      const data = await response.json();
      if (requestId !== openLibraryRequestId) return;
      openLibraryTotal = Number(data.numFound) || 0;
      const books = (data.docs || []).filter(book =>
        typeof book.key === "string" &&
        book.key.startsWith("/works/") &&
        typeof book.title === "string" &&
        book.title.trim() &&
        Array.isArray(book.author_name) &&
        book.author_name.some(author => typeof author === "string" && author.trim()) &&
        !openLibraryKeys.has(book.key)
      );
      for (const book of books) {
        openLibraryKeys.add(book.key);
        if (openLibraryBooks.length + addedBooks.length < targetCount) {
          addedBooks.push(book);
        } else {
          openLibraryPendingBooks.push(book);
        }
      }
      openLibraryPage += 1;
      pagesFetched += 1;
    }

    if (requestId !== openLibraryRequestId) return;
    openLibraryBooks.push(...addedBooks);
    if (addedBooks.length) openLibraryResults.append(...addedBooks.map(makeOpenLibraryCard));
    openLibraryCount.textContent = `${openLibraryBooks.length.toLocaleString()} of up to 1,000 books`;
    openLibraryMessage.textContent = openLibraryBooks.length
      ? "Only catalog records with an author name are shown."
      : "No books with author names found. Try another search.";
    openLibraryMessage.hidden = openLibraryBooks.length > 0;
    if (openLibraryLoadMore) {
      openLibraryLoadMore.hidden = openLibraryBooks.length >= openLibraryMaxBooks ||
        (!openLibraryPendingBooks.length && (openLibraryPage - 1) * openLibraryPageSize >= openLibraryTotal);
    }
  } catch (error) {
    if (error.name === "AbortError" || requestId !== openLibraryRequestId) return;
    openLibraryCount.textContent = openLibraryBooks.length
      ? `${openLibraryBooks.length.toLocaleString()} books loaded`
      : "Catalog unavailable";
    openLibraryMessage.textContent = openLibraryBooks.length
      ? "Could not load more books right now. Please try again."
      : "Could not load Open Library right now. Check your internet connection and try again.";
    openLibraryMessage.hidden = false;
    console.error("Open Library search failed:", error);
  } finally {
    if (requestId === openLibraryRequestId && openLibraryLoadMore) {
      openLibraryLoadMore.disabled = false;
    }
  }
}

if (openLibrarySearch && openLibraryQuery) {
  openLibrarySearch.addEventListener("submit", event => {
    event.preventDefault();
    loadOpenLibraryBooks(openLibraryQuery.value.trim());
  });
  openLibraryLoadMore?.addEventListener("click", () => loadOpenLibraryBooks(null));
  loadOpenLibraryBooks(openLibraryQuery.value.trim());
}

async function loadBookEditionReference() {
  if (!bookPreview || !openLibraryEditionFacts) return;
  const title = bookPreview.dataset.title || "";
  const author = bookPreview.dataset.author || "";
  const params = new URLSearchParams({
    title,
    author,
    fields: "key,title,author_name,first_publish_year,publisher,isbn,number_of_pages_median",
    limit: "10"
  });

  try {
    const response = await fetch(`https://openlibrary.org/search.json?${params}`);
    if (!response.ok) throw new Error(`Open Library returned ${response.status}`);
    const data = await response.json();
    const normalize = value => String(value || "").normalize("NFKC").replace(/\s+/g, " ").trim().toLowerCase();
    const match = (data.docs || []).find(book =>
      normalize(book.title) === normalize(title) &&
      Array.isArray(book.author_name) &&
      book.author_name.some(name => normalize(name) === normalize(author))
    );

    if (!match) {
      openLibraryEditionFacts.textContent = "No exact catalog match found. Ask the seller to confirm the edition and ISBN.";
      return;
    }

    const facts = [
      ["First published", match.first_publish_year],
      ["Publisher", Array.isArray(match.publisher) ? match.publisher.slice(0, 3).join(", ") : ""],
      ["Typical page count", match.number_of_pages_median ? `${match.number_of_pages_median} pages` : ""],
      ["Catalog ISBNs", Array.isArray(match.isbn) ? match.isbn.slice(0, 3).join(", ") : ""]
    ].filter(([, value]) => value);

    if (!facts.length) {
      openLibraryEditionFacts.textContent = "No edition details are available for this catalog match.";
      return;
    }

    const list = document.createElement("ul");
    for (const [label, value] of facts) {
      const item = document.createElement("li");
      const heading = document.createElement("strong");
      heading.textContent = `${label}: `;
      item.append(heading, document.createTextNode(String(value)));
      list.append(item);
    }
    openLibraryEditionFacts.replaceChildren(list);
  } catch (error) {
    openLibraryEditionFacts.textContent = "Edition details are unavailable right now. Ask the seller to confirm the ISBN.";
    console.error("Open Library edition lookup failed:", error);
  }
}

if (bookPreview) loadBookEditionReference();
