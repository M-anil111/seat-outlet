   
    /* =====================================================
       ARTIST SUBMENU SCROLL
    ===================================================== */
    
    function scrollToElement(id) {
      const target = document.getElementById(id);
      if (!target) return;
    
      const isMobile = window.innerWidth < 992;
      const offset   = isMobile ? 0 : 92; 
    
      const elementPosition = target.getBoundingClientRect().top + window.pageYOffset;
      const offsetPosition  = elementPosition - offset;
    
      window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
      });
    
      document.querySelectorAll('#artistTabs .nav-link')
          .forEach(btn => btn.classList.remove('active'));
    
      const activeBtn = document.querySelector(`#artistTabs button[onclick*="${id}"]`);
      if (activeBtn) activeBtn.classList.add('active');
    }
    
    /* =====================================================
     ARTIST SUBMENU SCROLL End
    ===================================================== */ 
  
  /* =====================================================
     ARTIST SUBMENU CHANGE ON SCROLL
  ===================================================== */
  
  const tabs = document.querySelectorAll('#artistTabs .nav-link');
  const sections = document.querySelectorAll('.tab-section');
  const offset = 120;
  
  window.addEventListener('scroll', () => {
      let currentId = null;
  
      sections.forEach(section => {
          const rect = section.getBoundingClientRect();
          if (rect.top <= offset && rect.bottom > offset) {
              currentId = section.id;
          }
      });
  
      if (currentId) {
          tabs.forEach(tab => {
              tab.classList.toggle(
                  'active',
                  tab.dataset.target === currentId
              );
          });
      }
  });
  
  /* =====================================================
     ARTIST SUBMENU CHANGE ON SCROLL End
  ===================================================== */